<?php
header('Content-Type: application/json; charset=utf-8');
include 'res/class/valid-class.php';
include 'res/class/csv-class.php';
include 'res/class/mail-class.php';

/*<Переменные>*/
$file = $_FILES['my_file'];

$POST = array(
        'name' => $_POST['name'],
        'phone' => $_POST['phone'],
        'contact_email'=>$_POST['mail'],
        'text'=>$_POST['text']
        );

$mailTo = array("skobelkinsa@mmentor.ru");

$csv_file = 'res/data/zayavki.csv';

$upload_dir = 'res/data/upload/';

$allowed_ext = array('jpg','jpeg','png','gif','doc','docx');

$site = $_SERVER['SERVER_NAME'];

$ipv4 = $_SERVER['REMOTE_ADDR'];
//Кол-во возможных отправок почты до таймаута
$count_valid = 3;

$times = 60*60*30;//время лимита на повторную отправку(дефолтно 30 мин) в секундах
/*</Переменные>*/

/*<Проверка на дурака>*/
if (stristr($_SERVER['HTTP_REFERER'], $site)  === FALSE){
    echo json_encode(array('error'=>44,'message'=>"Что-то пошло не так"));exit();
}
/*</Проверка на дурака>*/

/*<Проверка на лимит>*/
$k = 0;
$csv = new parseCSV();
$csv->auto($csv_file);
foreach ($csv->data as $value):
    $ip = $value['ip'];
    $time = (int)$value['time'];
    if($ip == $ipv4){
        if($time+$times >= time()){
            $k++;
            if($k>=$count_valid){echo json_encode(array('error'=>33,'message'=>"Привышен лимит заказов через сайт(".$count_valid." заказа раз в полчаса)"));exit();}
        }
    }
endforeach;
unset($csv);
/*</Проверка на лимит>*/

/*<валидация>*/
$valid = new validation;
$valid->addSource($POST);
$valid->addRule('contact_email', 'email', true, 1, 255, true);
$rules_array = array(
    'name'=>array('type'=>'string',  'required'=>true, 'min'=>3, 'max'=>50, 'trim'=>true),
    'phone'=>array('type'=>'string', 'required'=>true, 'min'=>6, 'max'=>18, 'trim'=>true),
    'text'=>array('type'=>'string', 'required'=>false, 'min'=>0, 'max'=>160, 'trim'=>true)
    );
$valid->addRules($rules_array);
$valid->run();
if(sizeof($valid->errors) > 0)
{
    echo json_encode(array('error'=>11,'message'=>"Поле неверно заполнено!"));exit();
}
if(isset($file)) {
	      $ext = explode('.', $file['name']);
    		$ext = array_pop($ext);
    		$file_new = $upload_dir.time().".".$ext;

	      if(!in_array($ext,$allowed_ext)){
 		     echo json_encode(array('error'=>33,'message'=>"Неверный формат файла!"));exit();
	      }
	      if($file["size"] > 1024*3*1024)
		   {
 		    echo json_encode(array('error'=>27,'message'=>"Размер файла превышает три мегабайта!"));exit();
		   }
		   
		   if(is_uploaded_file($file["tmp_name"]))
		   {
		     move_uploaded_file($file["tmp_name"], $file_new);
		   } else {
		      echo json_encode(array('error'=>58,'message'=>"Файл не сохранен!"));exit();
		   }
}
/*</валидация>*/
/*<Если всё валидно и правильно>*/
$POST = $valid->sanitized;
$csv = new parseCSV();
$csv->save($csv_file, array(array($ipv4, $POST['name'], $POST['phone'], time())), true);

$m=new Mail();
$m->From( "noreply@".$site ); // от кого отправляется почта
$m->To( $mailTo ); // кому адресованно
$m->Subject( "Заявка с сайта ".$site );

if(isset($POST['name']) && $POST['name'] != ''){$str = "Имя: ".$POST['name']."\n";}
if(isset($POST['phone']) && $POST['phone'] != ''){$str .= "Телефон: ".$POST['phone']."\n";}
if(isset($POST['text']) && $POST['text'] != ''){$str .= "Дополнительно: ".$POST['text']."\n";}
if(isset($POST['contact_email']) && $POST['contact_email'] != ''){$str .= "E-mail: ".$POST['contact_email']."\n";}
if(isset($file)) $m->Attach( $file_new, "", "", "attachment");
$m->Body($str);
$m->Send();    // а теперь пошла отправка

echo json_encode(array('error'=>0,'message'=>"Успешно отправлено!"));exit();

/*</Если всё валидно и правильно>*/
?>
