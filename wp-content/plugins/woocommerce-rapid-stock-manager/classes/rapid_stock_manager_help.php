<?php
/**
 * Class Help
 * @author ishouty ltd. London
 * @date 2016
 */
class rapid_stock_manager_help {

    /**
     * @var string Id of this plugin for internationalisation used in __ and _e
     */
    public $id = 'woocommerce-rapid-stock-manager';

    private $admin_url = 'admin.php?page=update_stock_rapid';
    private $help_folder = '/help/';

    function __construct () {
        $this->help_folder = __DIR__ . '/..' . $this->help_folder;
    }
    
    public function get_help_link($help_page){
        return admin_url($this->admin_url . '&rapid-selector-view=help&help-page='.$help_page);
    }
    
    private function get_help_filename($help_page){
        if( !$help_page || !isset($help_page) || empty($help_page) || $help_page == "" ){
            return false;
        }
        $help_filename = $this->help_folder.$help_page;
        if( file_exists($help_filename) && is_file($help_filename) ){
            return $help_filename;
        }
        if( file_exists($help_filename.'.php') ){
            return $help_filename.'.php';
        }
        if( file_exists($help_filename.'.html') ){
            return $help_filename.'.html';
        }
        if( file_exists($help_filename.'.txt') ){
            return $help_filename.'.txt';
        }
        return false;
    }

    private function get_include_contents($filename) {
        if (is_file($filename)) {
            ob_start();
            include $filename;
            return ob_get_clean();
        }
        return false;
    }
    
    public function get_help_content($help_page = 'index'){
        if( $help_page == "" ){
            $help_page = "index";
        }
        $help_full_filename = $this->get_help_filename($help_page);
        if( !$help_full_filename ){
            return __('Help page does not exist. Page: ',$this->id) . $help_page;
        }
        $help_page_contents = $this->get_include_contents($help_full_filename);
        if( !$help_page_contents ){
            return __('Could not read help page contents. Page: ',$this->id) . $help_page;
        }
        return $help_page_contents;
    }
    
    public function view($help_page){
        $help_page_contents = $this->get_help_content($help_page);
        ?>
        <div class="col-wrap help-container">
            <?php
            echo $help_page_contents;
            ?>
        </div>
        <?php
    }
}