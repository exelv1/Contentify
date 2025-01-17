<?php namespace App\Modules\Config\Http\Controllers;

use App\Modules\Config\SettingsBag;
use Contentify\Vendor\MySqlDump;
use Str, Redirect, Input, File, DB, Config, View, BackController;

class AdminConfigController extends BackController {

    /**
     * Path and file name of the log file.
     * Note that chagning this constant won't 
     * change where Monolog creates the file.
     */
    const LOG_FILE = '/logs/laravel.log';

    protected $icon = 'cog';
    
    public function getIndex()
    {
        if (! $this->checkAccessRead()) return;

        $settings = DB::table('config')->get();

        $settingsBag = new SettingsBag; // This is some kind of a helper model to store settings

        // We have to loop over all settings and check if they are listed as fillable:
        foreach ($settings as $setting) {
            $settingName = str_replace('app.', 'app::', $setting->name);
            if (in_array($settingName, $settingsBag->getFillable())) {
                $settingsBag->$settingName = $setting->value;
            }
        }
        
        $this->pageView('config::admin_index', compact('settingsBag'));
    }

    /**
     * Updates the settings.
     * Note that we have to create the parameter $id eventhough we won't use it:
     * The update method inherits from BackController->update($id).
     * We allow $id to be null so we do not have to pass an argument.
     * 
     * @param mixed $id Unused parameter
     */
    public function update($id = null)
    {
        if (! $this->checkAccessUpdate()) return;

        $settingsBag = new SettingsBag;
        $settingsBag->fill(Input::all());

        if (! $settingsBag->isValid()) {
            return Redirect::to('admin/config')
                ->withInput()->withErrors($settingsBag->getErrors());
        }

        // Save the settings one by one:
        foreach ($settingsBag->getFillable() as $settingName) {
            $settingRealName = str_replace('app::', 'app.', $settingName);
            DB::table('config')->whereName($settingRealName)->update(['value' => $settingsBag->$settingName]);
        }

        $this->alertFlash(trans('app.updated', [$this->controller]));
        return Redirect::to('admin/config');
    }

    /**
     * This action method executes the phpinfo() command.
     * It uses a dirty hack to override the CSS classes phpinfo() uses.
     *
     * @return void
     */
    public function getInfo()
    {
        if (! $this->checkAccessRead()) return;

        ob_start();

        // We tell phpinfo() what infos to show, so we avoid to show the PHP credits:
        phpinfo(INFO_GENERAL | INFO_CONFIGURATION | INFO_MODULES | INFO_ENVIRONMENT | INFO_VARIABLES); 
        
        preg_match('%<style type="text/css">(.*?)</style>.*?(<body>.*</body>)%s', ob_get_clean(), $matches);
        
        $this->pageOutput('<div class="phpinfodisplay"><style type="text/css">'."\n".
             join("\n",
                 array_map(
                     create_function(
                         '$i',
                         'return ".phpinfodisplay " . preg_replace( "/,/", ",.phpinfodisplay ", $i );'
                         ),
                     preg_split('/\n/', $matches[1]) // $matches[1] = style information
                     )
                 ).
             "{}\n
             .phpinfodisplay { overflow-x: scroll }\n
             .phpinfodisplay td,.phpinfodisplay  th { border: 1px solid silver; overflow-wrap: break-word; }\n
             .phpinfodisplay .h { background-color: #DDD }\n
             .phpinfodisplay .e { background-color: #EEE }\n
             .phpinfodisplay .v { background-color: white }</style>\n". // Override the classes
             $matches[2]. // $matches[2] = body information
             "\n</div>\n");
    }

    /**
     * Optimize Database
     * 
     * @return void
     */
    public function getOptimize()
    {
        if (! $this->checkAccessUpdate()) return;

        switch (Config::get('database.default')) { // Retrieve the default database type from the config
            case 'mysql':
                $tables = DB::select('SHOW TABLES');
                foreach ($tables as $table) {
                    DB::select('OPTIMIZE TABLE '.current($table));
                }
                break;
            default:
                $this->alertError(trans('config::not_supported', [Config::get('database.default')]));
                return;
        }

        $this->alertSuccess(t('Database optimized.'));
    }

    /**
     * Create MySQL dump
     * 
     * @return void
     */
    public function getExport()
    {
        if (! $this->checkAccessRead()) return;

        switch (Config::get('database.default')) { // retrieve the default database type from the config
            case 'mysql':
                $dump = new MySqlDump();

                $con        = Config::get('database.connections.mysql');
                $time       = time();
                $filename   = storage_path().'/database/'.$time.'.sql';

                $dump->host     = $con['host'];
                $dump->user     = $con['username'];
                $dump->pass     = $con['password'];
                $dump->db       = $con['database'];
                $dump->filename = $filename;
                $dump->start();

                $this->alertSuccess(
                    trans('config::db_export'), 
                    trans('config::db_file'));
                break;
            default:
                $this->alertError(trans('config::not_supported', [Config::get('database.default')]));
                return;
        }
    }

    /**
     * Show the log file content
     * 
     * @return void
     */
    public function getLog()
    {
        if (! $this->checkAccessRead()) return;

        $fileName = storage_path().self::LOG_FILE;
        if (File::exists($fileName)) {
            $lines      = file($fileName);
            $content    = '';

            foreach ($lines as $line) {
                if (Str::startsWith($line, '[')) {
                    if ($content) {
                        $content .= '</div></div>';
                    }
                    $line = substr($line, 0, 21).'</span>'.substr($line, 21);
                    $content .= '<div class="item"><span class="date">'.$line.'<div class="stack">';
                } else {
                    $content .= '<div class="line">'.$line.'</div>';
                }
            }

            if ($content) {
                $content .= '</div></div>';
            }

            $size = File::size($fileName);

            $this->pageView('config::show_log', compact('content', 'size'));
        } else {
            $this->alertInfo(trans('config::log_empty'));
        }
    }

    /**
     * Delete the log file
     * 
     * @return void
     */
    public function clearLog()
    {
        $fileName = storage_path().self::LOG_FILE;

        if (File::exists($fileName)) {
            File::delete($fileName);
        }

        $this->alertSuccess(trans('app.deleted', [$fileName]));
    }

}