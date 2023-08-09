<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/crm/ajax/common_ajax_header.php';
CModule::IncludeModule('iblock');

$people = [];

foreach ($users as $id => $user) {
    $people[$id] = $user['name'];

}

$from_otch = date('Y-m-d', strtotime($_REQUEST['from']));
$to_otch = date('Y-m-d', strtotime($_REQUEST['to']));


function login($url, $login, $pass)
{


    $ch = curl_init();
    if (strtolower((substr($url, 0, 5)) == 'https')) { // если соединяемся с https
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    }

    curl_setopt($ch, CURLOPT_URL, htmlspecialchars_decode($url));
    // откуда пришли на эту страницу
    curl_setopt($ch, CURLOPT_REFERER, htmlspecialchars_decode($url));
    // cURL будет выводить подробные сообщения о всех производимых действиях
    curl_setopt($ch, CURLOPT_VERBOSE, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "AUTH_FORM=Y&TYPE=AUTH&USER_LOGIN=123&USER_PASSWORD=123&Login=&USER_REMEMBER=Y&captcha_sid=");
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (Windows; U; Windows NT 5.0; En; rv:1.8.0.2) Gecko/20070306 Firefox/1.0.0.4");
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    //сохранять полученные COOKIE в файл
    curl_setopt($ch, CURLOPT_COOKIEJAR, $_SERVER['DOCUMENT_ROOT'] . '/cookie.txt');
    $result = curl_exec($ch);


    curl_close($ch);

    return $result;

}

// чтение страницы после авторизации
function Read($url)
{

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, htmlspecialchars_decode($url));
    // откуда пришли на эту страницу
    curl_setopt($ch, CURLOPT_REFERER, htmlspecialchars_decode($url));
    //запрещаем делать запрос с помощью POST и соответственно разрешаем с помощью GET
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    //отсылаем серверу COOKIE полученные от него при авторизации
    curl_setopt($ch, CURLOPT_COOKIEFILE, $_SERVER['DOCUMENT_ROOT'] . '/cookie.txt');
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (Windows; U; Windows NT 5.0; En; rv:1.8.0.2) Gecko/20070306 Firefox/1.0.0.4");

    $result = curl_exec($ch);


    curl_close($ch);

    return $result;

}

$urlLog = "http://" . $_SERVER["HTTP_HOST"] . "/bitrix/admin/";
$login = '123';
$password = '123';
$auth = login($urlLog, $login, $password);
foreach ($people as $user_id => $name) {

    $urlTo = "http://" . $_SERVER["HTTP_HOST"] . "/crm/admin/people_new/index2.php?man=" . $user_id . "&from=" . $from_otch . '&to=' . $to_otch;         // Куда данные послать

    $styles = file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/bitrix/templates/crm/template_styles.css');

    $styles .= file_get_contents($_SERVER['DOCUMENT_ROOT'] . '/bitrix/templates/crm/css/bootstrap.min.css');

    $styles .= '* {
  
  font-family: "DejaVu Sans", sans-serif;
}';

    $scripts = 'async function getAjaxTable(man)
{
    const response = await fetch("/crm/admin/people_archive_reports/ajax-table/ajax-table-archive.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "Accept": "application/text",
            "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify(datasForTable[man])
    });
    const result = await response.json();

    if (result.status == "success")
    {
        $(`.money-table-for-${man}`).append(result.html);
    }
}';


    if ($auth) {

        $doc = new DOMDocument();
        $html = Read($urlTo);


        $doc->loadHTML($html);
        $list = $doc->getElementsByTagName("nav");
        while ($list->length > 0) {
            $nav = $list->item(0);
            $nav->parentNode->removeChild($nav);
        }

        $selector = new DOMXPath($doc);
        foreach ($selector->query('//div[contains(attribute::class, "hidden-print")]') as $e) {
            $e->parentNode->removeChild($e);
        }
        $new_styles = $doc->createElement('style', $styles);
        $new_scripts = $doc->createElement('script', $scripts);
        foreach ($doc->getElementsByTagName('head') as $head) {
            $head->appendChild($new_styles);
            $head->appendChild($new_scripts);
        }

        $rep_folder = $_SERVER['DOCUMENT_ROOT'] . '/crm/admin/people_archive_reports/' . $user_id . '/';

        if (!is_dir($rep_folder)) {
            mkdir($rep_folder);
        }

        $doc->saveHTMLFile($rep_folder . "" . $from_otch . ".html");

        sleep(15);
    }

}




