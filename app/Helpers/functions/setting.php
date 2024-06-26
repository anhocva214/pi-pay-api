<?php 
function get_setting_value($key = "") {
    $repository = app(\App\Repositories\SettingRepository::class);
    $setting = $repository->findByKey('setting_key',$key);
    if(!$setting) {
        return null;
    }
    return $setting->setting_value ?? "";
}