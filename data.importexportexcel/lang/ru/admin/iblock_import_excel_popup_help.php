<?
/**
 * Copyright (c) 5/3/2021 Created By/Edited By nasekinid nasekinid8591@yandex.ru
 */

$MESS["KDA_IE_HELP_TAB1"] = "Видео-инструкции";
$MESS["KDA_IE_HELP_TAB1_ALT"] = "Видео-инструкции по настройке импорта";
$MESS["KDA_IE_HELP_TAB2"] = "FAQ";
$MESS["KDA_IE_HELP_TAB2_ALT"] = "Вопросы и ответы по работе модуля";
$MESS["KDA_IE_HELP_VIDEO_COMMON"] = "Основная инструкция по импорту";
$MESS["KDA_IE_HELP_VIDEO_ELEMENT_SECTIONS"] = "Общая инструкция по импорту разделов (с возможностью привязки элемента к нескольким разделам)";
$MESS["KDA_IE_HELP_VIDEO_SECTIONS_SEP_LINES"] = "Импорт файлов с разделами в отдельных строках";
$MESS["KDA_IE_HELP_VIDEO_SECTIONS_WO_ELEMENTS"] = "Импорт разделов без элементов";
$MESS["KDA_IE_HELP_VIDEO_IMPORT_OFFERS"] = "Инструкция по загрузке торговых предложений";
$MESS["KDA_IE_HELP_VIDEO_IMPORT_EXTERNAL_FIELDS"] = "Загрузка дополнительных полей (которых нет в файле)";
$MESS["KDA_IE_HELP_VIDEO_DIFFERENT_PRODUCERS"] = "Импорт прайсов разных поставщиков с деактивацией товаров или обнулением остатков";
$MESS["KDA_IE_HELP_VIDEO_PRICE_AND_QUANTITIES"] = "Импорт цен и остатков";
$MESS["KDA_IE_HELP_VIDEO_MULTIPLE_PROPERTIES"] = "Импорт множественных свойств и описаний к свойствам";
$MESS["KDA_IE_HELP_VIDEO_IMAGES"] = "Импорт изображений";
$MESS["KDA_IE_HELP_VIDEO_PROPERTIES_SEPARATE"] = "Импорт свойств из одной ячейки Excel-файла";
$MESS["KDA_IE_HELP_VIDEO_EMAIL_FTP_CRON"] = "Импорт с Email-адреса и с ftp по крону";
$MESS["KDA_IE_HELP_VIDEO_MASS_PROP_CREATE"] = "Массовое создание свойств";
$MESS["KDA_IE_HELP_VIDEO_CLOUD_SERVISES"] = "Загрузка файлов с облачных сервисов";
$MESS["KDA_IE_HELP_VIDEO_PRICES_EXT"] = "Загрузка цен в расширенном режиме управления";
$MESS["KDA_IE_FAQ_QUEST_PICTURES"] = "Как импортировать картинки?";
$MESS["KDA_IE_FAQ_ANS_PICTURES"] = "Вы можете посмотреть видео-инструкцию по импорту картинок <a href=\"https://www.youtube.com/watch?v=vkQQTlrJKN4\" target=\"_blank\">https://www.youtube.com/watch?v=vkQQTlrJKN4</a><br><br>
	Импорт картинок возможен 2-мя способами:
	<ul>
		<li>Загрузка по внешней ссылке. Например: https://mdata.yandex.net/i?path=b0228152649_img_id6362633435892454257.jpeg</li>
		<li>Загрузка по ссылке с Вашего сайта. Например: /upload/images/image.jpg. Для этого картинка предварительно должна быть загружена в папку /upload/images/ (или любую другую на Ваше усмотрение). Загрузить картинки на сайт Вы можете либо по FTP, либо через административную панель сайта в разделе \"Контент\" -> \"Структура сайта\" -> \"Файлы и папки\". Загружать через панель управления можно архивом с последующей его распаковкой.</li>
	</ul>";
$MESS["KDA_IE_FAQ_QUEST_SLOW_IMPORT"] = "Почему импорт проходит очень медленно?";
$MESS["KDA_IE_FAQ_ANS_SLOW_IMPORT"] = "Есть несколько причин медленного импорта:
	<ul>
		<li>Из файла импортируются картинки. Если загрузка картинок происходит по внешней ссылке или на картинки накладывается авторский знак, то это может очень сильно замедлить импорт. Попробуйте отключить импорт картинок и проверить скорость импорта.</li>
		<li>При большом объеме товаров на сайте скорость импорта сильно падает, если идентификация товаров или торговых предложений производится по свойствам (например по артикулу). В этом случае рекомендуем свойства для идентификации перенести в поле \"Внешний код\". Если у Вас используется несколько свойство для идентификации, то Вы можете перенести их во внешний код в таком виде \"[Свойство 1]_[Свойство 2]\". При идентификации по внешнему коду импорт пройдет в разы быстрее, т.к. \"Внешний код\" содержится в таблице элемента, в то время как свойства содержатся в отдельной таблице.</li>
		<li>Загружается очень большой файл в формате xls или xslx. Здесь может возникать ситуация, когда за один шаг импорта модуль успевает только профитать файл, а на загрузку данных уже не хватает времени, т.к. время выполнения скрипта импорта ограничено. В данном случае рекомендуем Вам конвертировать файл в csv перед импортом. На чтение csv-файла требуется минимум ресурсов и минимум времени.</li>
	</ul>";
$MESS["KDA_IE_FAQ_QUEST_MULTI_PICTURES"] = "Как загрузить несколько картинок в одно поле?";
$MESS["KDA_IE_FAQ_ANS_MULTI_PICTURES"] = "Импорт нескольких картинок возможен только во множественные свойства типа \"Файл\". В файле импорта картинки должны быть указаны в одной ячейке через разделитель множественных свойств (как и все другие множественные свойства). Например: \"/upload/images/image1.jpg; /upload/images/image2.jpg; /upload/images/image3.jpg\".";
$MESS["KDA_IE_FAQ_QUEST_MULTI_SECTIONS"] = "Как импортировать один товар в несколько разделов?";
$MESS["KDA_IE_FAQ_ANS_MULTI_SECTIONS"] = "Данный вопрос детально рассмотрен в данной видео-инструкции <a href=\"https://www.youtube.com/watch?v=jfgaadkLQGU\" target=\"_blank\">https://www.youtube.com/watch?v=jfgaadkLQGU</a>";
$MESS["KDA_IE_FAQ_QUEST_CRON"] = "Как настроить импорт по крону?";
$MESS["KDA_IE_FAQ_ANS_CRON"] = "На страницах модуля в правом верхнем углу есть зеленая кнопка \"Настройка cron\". <br>
	По этой кнопке открывается форма, в которой Вы можете выбрать профиль и время запуска крона.<br> 
	Также там есть параметр \"Путь к php\", который зависит от настроек Вашего хостинга, но в большинстве случаев он будет таким \"/usr/bin/php\".<br>
	После создания крона задание будет записано в файле \"/bitrix/crontab/crontab.cfg\" Вы увидите строку вида <br>
	<b><i>0 2 * * * /usr/bin/php -f /home/bitrix/yoursite.com/bitrix/php_interface/include/data.importexportexcel/cron_frame.php 0 >/home/bitrix/yoursite.com/bitrix/php_interface/include/data.importexportexcel/logs/0.txt</i></b><br><br>
	Эту команду Вам необходимо будет перенести в настройки крона в панели управления хостингом.
	Команда содержит следующие составляющие:<br>
	<b><i>0 2 * * *</i></b> - время запуска скрипта<br>
	<b><i>/usr/bin/php</i></b> - путь к php<br>
	<b><i>/home/bitrix/yoursite.com/bitrix/php_interface/include/data.importexportexcel/cron_frame.php 0</i></b> - непосредственно запускаемый скрипт. Если у Вас в настройкх хостинга есть пункт типа \"Выполнить php скрипт\", то в строке со скриптом нужно будет вставить только эту часть строки. Здесь \"0\" на конце - это идентификатор профиля импорта.<br>
	<b><i>>/home/bitrix/yoursite.com/bitrix/php_interface/include/data.importexportexcel/logs/0.txt</i></b> - путь к файлу с логами импорта. Без указания этого файла Вам в дальнейшем будет сложно отследить результаты импорта по крону.<br><br>				
	
	Параметр \"Установить автоматически\" позволяет автоматически создать задание в кроне, но предусмотрен в основном для виртуальной Битрикса, т.к. на большинстве другх хостингов он работать не будет. Обратите внимание, что функция \"Установить автоматически\" презапишет все Ваши задания в кроне из файла \"/bitrix/crontab/crontab.cfg\".";
$MESS["KDA_IE_FAQ_QUEST_BOOL"] = "Как загрузить булево значение?";
$MESS["KDA_IE_FAQ_ANS_BOOL"] = "Для передачи булевых полей используйте следующие значения:
	<ul>
		<li>\"1\", \"да\", \"yes\", \"y\" - истина</li>
		<li>\"0\", \"нет\", \"not\", \"n\" - ложь</li>
	</ul>
	Регистр не имеет значения. ";
?>
