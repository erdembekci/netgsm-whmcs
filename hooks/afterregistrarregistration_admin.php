<?php
$hook = array(
    'hook' => 'AfterRegistrarRegistration',
    'function' => 'AfterRegistrarRegistration_admin',
    'hook_tr' => 'Alan Adı Kayıt',
    'title' => '',
    'description' => array(
        'turkish' => 'Bir domain kayıt edildikten sonra yöneticiye mesaj gönderir.',
        'english' => 'After domain registration it sends a message to the admin'
    ),
    'type' => 'admin',
    'extra' => '',
    'defaultmessage' => 'Yeni domain kayit edildi. ({domain})',
    'variables' => '{domain}'
);

if (!function_exists('AfterRegistrarRegistration_admin')) {
    function AfterRegistrarRegistration_admin($args)
    {


        $service = new SmsService();

        $template = $service->getTemplateDetails(__FUNCTION__);
        if ($template == false) {
            return null;
        }
        $templateRow = $template->fetch(PDO::FETCH_ASSOC);
        if ($templateRow['active'] == 0) {
            return null;
        }
        $message = $templateRow['template'];
        if (empty($templateRow['admingsm'])) {
            return null;
        }
        $phonenumbers = explode(",", $templateRow['admingsm']);

        $settings = $service->getSettings();
        $settingsRow = $settings->fetch(PDO::FETCH_ASSOC);
        if (!$settingsRow['usercode'] || !$settingsRow['password']) {
            return null;
        }
        if (strpos($message, "{domain}") !== false) {
            $message = str_replace("{domain}", $args['params']['sld'].".".$args['params']['tld'], $message);
        }

        $SMSArray = [];
        foreach ($phonenumbers as $phonenumber) {

            $result = $service->clearPhoneNumber($phonenumber);
            $phonenumber = $result['phonenumber'];
            $validity = $result['validity'];
            if ($validity === false) {
                continue;
            }

            if(ctype_digit($phonenumber)) {
                array_push($SMSArray, new SMS($message, trim($phonenumber)));
                $request = new Request($templateRow['title'], $SMSArray, $settingsRow['usercode'], $settingsRow['password']);
                $request->prepareXMLRequest();
                $request->XMLPOST();
            }
        }

    }
}

return $hook;