<?php

/**
 * autopkg_api module class
 *
 * @package munkireport
 * @author tuxudo
 **/
class Autopkg_api_controller extends Module_controller
{
    /*** Protect methods with auth! ****/
    function __construct()
    {
        // Store module path
        $this->module_path = dirname(__FILE__);
    }

    /**
     * Default method
     * @author tuxudo
     *
     **/
    public function index()
    {
        echo "You've loaded the autopkg_api module!";
    }

    public function admin()
    {
        $obj = new View();
        $obj->view('autopkg_api_admin', [], $this->module_path.'/views/');
    }

    /**
     * Returns hash of all modules' scripts
     *
     * @author tuxudo
     **/
    public function get_module_script_hash()
    {
        $all_modules = getMrModuleObj()->getModuleList(true);
        $hash_total = "";

        // Process each module
        foreach ($all_modules as $path) {

            // Check if script directory exists
            if (! file_exists($path."/scripts/")) {
                continue;
            }

            $all_files = scandir($path."/scripts/");
            $files = array_diff($all_files, array('.', '..'));

            // Process each file in scripts directory
            foreach ($files as $file) {
                if (strpos(strtolower($file), '.zip') === false && substr( $file, 0, 1 ) !== "."){
                    $hash_total = $hash_total.hash_file('xxh3', $path."/scripts/".$file);
                }
            }
        }

        // Return JSON of script details
        return hash('xxh3', $hash_total);
    }

    /**
     * Removes the autopkg_api cache file
     *
     * @author tuxudo
     **/
    public function clean_autopkg_api_cache()
    {
        // Purge cache data from cache table
        $cached_data = munkireport\models\Cache::where('module', 'autopkg_api')
                        ->where('property', 'autopkg_api_json')
                        ->delete();

        // Return success
        jsonView(array('Status' => "SUCCESS"));
    }

    /**
     * Forces AutoPKG to rebuild client PKG by zeroing out script hash
     *
     * @author tuxudo
     **/
    public function force_autopkg_rebuild()
    {
        // Get cache data from the cache table
        $cached_data = munkireport\models\Cache::select('value')
                        ->where('module', 'autopkg_api')
                        ->where('property', 'autopkg_api_json')
                        ->value('value');

        // Check if we have a null result
        if (! $cached_data == null){

            $cached_data = json_decode($cached_data, true);
            $cached_data['script_hash'] = "0000000000";

            // Save new cache data to the cache table
            munkireport\models\Cache::updateOrCreate(
                [
                    'module' => 'autopkg_api',
                    'property' => 'autopkg_api_json',
                ],[
                    'value' => json_encode($cached_data),
                    'timestamp' => time(),
                ]
            );
        }

        // Return success
        jsonView(array('Status' => "SUCCESS"));
    }

    /**
     * Retrieve data in json format for AutoPKG API
     *
     **/
    public function get_autopkg_data_api()
    {
        $rebuild_clientpkg = FALSE;

        // Get cache data from the cache table
        $cached_data = munkireport\models\Cache::select('value')
                        ->where('module', 'autopkg_api')
                        ->where('property', 'autopkg_api_json')
                        ->value('value');

        // Get the current time
        $current_time = time();

        // Check if we have a null result
        if ($cached_data == null){
            // Yes, rebuild client PKG via AutoPKG
            $rebuild_clientpkg = TRUE;
            $cached_data = [];
            $cached_data['version'] = $GLOBALS['version'].".".date("Ymd")."00";

        } else {

            $cached_data = json_decode($cached_data, true);

            // Check if the on MODUELS array in .env has changed
            $modules_env = conf('modules', []);
            natcasesort($modules_env);
            if ($cached_data['modules_env'] !== implode(",", $modules_env)){
                // Yes, rebuild client PKG via AutoPKG
                $rebuild_clientpkg = TRUE;
            } 

            // Check if the modules' scripts have been changed
            if ($cached_data['script_hash'] !== $this->get_module_script_hash()){
                // Yes, rebuild client PKG via AutoPKG
                $rebuild_clientpkg = TRUE;
            }

            // Check if MunkiReport version has changed
            if ($cached_data['mr_version'] !== $GLOBALS['version']){
                // Yes, rebuild client PKG via AutoPKG
                $rebuild_clientpkg = TRUE;
            }
        }

        // Check if we are set to rebuild client PKG
        if ($rebuild_clientpkg){

            $modules_env = conf('modules', []);
            natcasesort($modules_env);
            $return_json['modules_env'] = implode(",", $modules_env);

            $return_json['script_hash'] = $this->get_module_script_hash();
            $return_json['last_rebuilt'] = $current_time;
            $return_json['last_polled'] = $current_time;
            $return_json['rebuild_pkg'] = "TRUE";
            $return_json['mr_version'] = $GLOBALS['version'];

            // Check if we already built a PKG today
            $today_date = date("Ymd");
            $version_array = explode(".", $cached_data['version']);
            $version_part = end($version_array);
            $version_date = substr($version_part, 0, 8);
            $version_ver = substr($version_part, 8, 2);
            if ($version_date = $today_date){
                $return_json['version'] = $GLOBALS['version'].".".$version_date .sprintf('%02d', $version_ver+1);
            } else {
                $return_json['version'] = $GLOBALS['version'].".".$today_date."01";
            }

        } else {
            $return_json = $cached_data;
            $return_json['last_polled'] = $current_time;
            $return_json['rebuild_pkg'] = "FALSE";
        }

        // Save new cache data to the cache table
        munkireport\models\Cache::updateOrCreate(
            [
                'module' => 'autopkg_api',
                'property' => 'autopkg_api_json',
            ],[
                'value' => json_encode($return_json),
                'timestamp' => $current_time,
            ]
        );

        jsonView($return_json);
    }

    /**
     * Retrieve data in json format for admin view
     *
     **/
    public function get_autopkg_data_view()
    {
        // Get cache data from the cache table
        $cached_data = munkireport\models\Cache::select('value')
                        ->where('module', 'autopkg_api')
                        ->where('property', 'autopkg_api_json')
                        ->value('value');

        // Check if we have a null result
        if ($cached_data == null){
            $cached_data = [];
        } else {

            $cached_data = json_decode($cached_data, true);

            // Check if the on MODUELS array in .env has changed
            $modules_env = conf('modules', []);
            natcasesort($modules_env);
            if ($cached_data['modules_env'] !== implode(",", $modules_env)){
                // Yes, will rebuild client PKG via AutoPKG
                $cached_data['rebuild_reason'] = "Modules";
            }

            // Check if the modules' scripts have been changed
            if ($cached_data['script_hash'] == "0000000000") {
                // Yes, force AutoPKG to rebuild
                $cached_data['rebuild_reason'] = "Manual";
            } elseif ($cached_data['script_hash'] !== $this->get_module_script_hash()){
                // Yes, will rebuild client PKG via AutoPKG
                $cached_data['rebuild_reason'] = "Scripts";
            }

            // Check if MunkiReport version has changed
            if ($cached_data['mr_version'] !== $GLOBALS['version']){
                // Yes, will rebuild client PKG via AutoPKG
                $cached_data['rebuild_reason'] = "MunkiReport Version";
            }
        }

        jsonView($cached_data);
    }
} // END class Autopkg_api_controller
