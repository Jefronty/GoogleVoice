# GoogleVoice
Upgraded **Aaron Parecki**'s original version of this class ~ [https://github.com/aaronpk](https://github.com/aaronpk)
Upgraded by **Kaddy Simba** ~ [http://simbamultimedia.com](http://simbamultimedia.com)
HTML DOM Class by **S.C. Chen** (me578022@gmail.com) ~ [http://simplehtmldom.sourceforge.net/manual.htm#section_quickstart](http://simplehtmldom.sourceforge.net/manual.htm#section_quickstart)

##Example
```php

include_once 'simple_html_dom.php'; //Load HTML Dom Class
include_once 'GoogleVoice.php'; //Load GoogleVoice Class

$authUser = 'iamasimba@gmail.com';
$pass = 'yourGvpass';
$city = 'San Diego, CA, United States'; //It's important to add the city where you created the account from for Google security purpose

$number = '+16191234567';
$sms = 'Hakuna matata';

$gv = 	new GoogleVoice($authUser, $pass, $city);
		$send = $gv->sendSMS($number, $sms);
		$send = json_decode($send,true);

		if($send['ok'] == true){
//Success
echo 'SMS Sent!';
}else{
//Error
echo 'Unable to send SMS';
}

```