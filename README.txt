=== Comment Images Reloaded ===
Contributors: wppuzzle, egolacrima
Tags: comment, image, attachment, images, comments, attachments, image zoom
Donate link: http://avovkdesign.com/bymecoffee
Requires at least: 3.2
Tested up to: 4.4.2
Stable tag: 2.1.3
License: GPLv2 or later 

Plugin allows users to add photos or images to their comments. Позволяет прикреплять фото к комментариям.

== Description ==

**CIR** ([Comment Images Reloaded](http://wp-puzzle.com/comment-images-reloaded/ "Comment Images Reloaded")) allows users to add photos, images or animation to post in comments. 
**The plugin monitors technical issues, which reduce number of queries to the database and the size of the page with hundreds of comments.**

Popular plugin [Comment Images](https://wordpress.org/plugins/comment-images/ "Comment Images") is taken as a basis and free solution of additional tasks is implemented for users of this product on its basis, operating peculiarities of some functionality are redone. 

= Improvements =
* Uploaded photo is automatically reduced to optimal size and only in this form it is published in comments (height or width of Photo of maximum of 1024 pixels is used by default).
* Plugin algorithm and displaying comments are modified, all these formidably reduced load on server (and consequently on hosting). 
* Correct operation of option is debugged which prohibits to add images in comments for all posts.

= New abilities =
1. Customization of image size which will be displayed in comments(change at any time — images responds to this option in new and existing comment):
 * Thumbnail — 150х150 pixels
 * Medium — 300х300 pixels 
 * Large — 1024×1024 pixels
 * Full — source image size 
 * Custom sizes are supported 
1. Page with plugin settings is implemented 
1. Limiting of files weight for downloaded custom images
1. Zooming of image in comment by clicking
1. One can change standard inscription above button commentary file selection
1. Output button "Choose file" in any part of comment form using special function
1. Data import function during transition from plugin Comment Images.

All new abilities and improvements are implemented using standard abilities of CMS WordPress.


= --- на русском --- =

**CIR** ([Comment Images Reloaded](http://wp-puzzle.com/comment-images-reloaded/ "Comment Images Reloaded")) позволяет прикреплять фотографии или другие изображения к каждому комментарию. 
**Плагин учитывает технические моменты, без соблюдения которых значительно растет нагрузка на БД, а страница сайта с сотнями комментариев непомерно увеличивается в размере.**

За основу взят популярный плагин [Comment Images](https://wordpress.org/plugins/comment-images/ "Comment Images") и на его базе реализовано бесплатное решение дополнительных задач пользователей этого продукта, переделаны принципы работы некоторого функционала.

= CIR обеспечивает: =
* получение всех данных об изображениях **всего лишь двумя запросами в БД**, не зависимо от количества комментариев на странице;
* **настройку размера выводимого в комментарии изображения** (на выбор доступны все зарегистрированные в WordPress размеры - `thumbnail`, `medium`, `large` + пользовательские); в полном размере изображение будет выведено только в том случае, если ширина и выcота стандартных картинок выставлены в `0` в настроках сайта;
* пользовательскую настройку максимального размера для загружаемых картинок (ограничен только настройками сервера в php.ini).

= Усовершенствования =
* Загружаемое фото автоматически уменьшается до оптимальных размеров и только в этом виде публикуется в комментарии (по-умолчанию, используется фото шириной или высотой максимум 1024 пикселя).
* Переработан алгоритм плагина и отображения комментариев, что позволило значительно уменьшить нагрузку на сервер (и, соответственно, хостинг).
* Отлажена корректная работа опции, запрещающей добавление картинок в комментарии для всех записей.

= Новые возможности =
* Индивидуальная настройка размера картинок, которые будут выводиться в комментариях (меняется в любое время — картинки  в новых и уже существующих комментариях реагируют на эту опцию):
 * Thumbnail — 150х150 пикселей,
 * Medium — 300х300 пикселей,
 * Large — 1024×1024 пикселей,
 * Full — исходный размер
 * Поддерживаются пользовательские размеры
* Реализована страница с настройками плагина.
* Ограничение веса файлов для загружаемых пользовательских изображений
* Увеличение картинки в комментарии по клику
* Стандартную надпись над кнопкой выбора файла комментатора можно изменить
* Вывод кнопки «Выберите файл» в любой части формы комментирования с помощью специальной функции
* Функция импорта данных при переходе из плагина Comment Images.


== Installation ==


= Automatic installation: =

1. Log-in to your WordPress admin interface.
1. Hover over "Plugins" and click on "Add New".
1. Under Search enter Comment Images Reloaded and click the "Search Plugins" button.
1. In results page click the "Install Now" link for "Comment Images Reloaded".
1. Click "Activate Plugin" to finish installation. You're done!

= Manual installation: =

1. Download [Comment Images Reloaded](https://downloads.wordpress.org/plugin/comment-images-reloaded.zip "Comment Images Reloaded") and unzip the plugin folder.
1. Upload `hierarchical-sitemap` folder into to the `/wp-content/plugins/` directory.
1. Go to WordPress dashboard and navigate to "Plugins" -> "Installed Plugins".
1. Activate "Comment Images Reloaded".


== Frequently Asked Questions ==

= Как вывести поле для вставки картинки вручную? =
Поле для вставки картинки автоматичсеки выводится после кнопки 'Отправить комментарий'. 
Вы можете расположить его в любом другом месте формы на свое усмотрение, используя специальные функции. Для этого нужно: 
1. В настройках плагина отметить галочкой опцию Поле вставки изображения (это отключает автоматичсекий вывод) 
1. В своем шаблоне формы в нужном месте вызать одну из следующих функций: 
 * чтобы вывести HTML код: if (function_exists("the_cir_upload_field")) { the_cir_upload_field(); } 
 * чтобы получить переменную с HTML кодом: if (function_exists("get_cir_upload_field")) { get_cir_upload_field(); }


== Screenshots ==

1. The default comment form in Twenty Sixteen with image upload form.
1. Comments Dashboard showing image for each comment.
1. Admin page with plugin settings.



== Changelog ==

= 2.1.3 =
* fix: correct working auto url feature

= 2.1.2 =
* fix: changed operation of action `comment_text` for themes which supports html5 comment-list
* new: added functions for manual paste of download field of picture in template of commenting form: - `the_cir_upload_field()` outputs html code field for inserting picture 
* new: added option for tripping automatic typing-out field in form comments 

= 2.1.1 =
* fix: fixed filename error while connecting zoom and connection of style files 
* fix: added validations on `WP_Error` while downloading file 

= 2.1.0 =
* new: added capability to activate image expansion in comment by clicking 
* new: added 'delete image' button on page with list of comments 
* new: added option for setting downloaded file size (maximum value is limited by `php.ini` settings) 
* new: added option for setting text before field of inserting picture 
* new: added author link (with ability to displug its output in options) 
* new: deleted metadata fields with date of attached image during final removal of comments (clear basket) 
* new: metadata of connected comments are found and deleted while deleting image from Media Library 
* fix: while importing from Comment Images:
 * `get_comments()` chooses not all comments but only those which included images 
 * copy of file is not created, just simply ID of existing input is found and its data are used 
 * checked existence of file, specified in metafield comment-image (on file and on `ABSPATH` + url)
 * metadata are not recorded and picture is not moved if file was not found on disc.
* fix: unnecessary characters of its name are deleted before saving file (only Latin letters, figures, dot, underscore and hyphen remain); if there is empty file name after that – it is generated from comment number and random number (from 100 to 900)

= 2.0.3 =
* fixed problem of incorrect displaying custom image sizes in plug-in settings 
* picture is displayed in comments with standard size 'large' by default.

= 2.0.2 =
* fixed data storage (transition from text format on serialized array) 

= 2.0.1 =
* added use of custom image size 
* added function for correct work `comments_array` on all themes 
* fixed file names 
* fixed storage of array data in base 

= 2.0 =
* Initial release **Comment Images Reloaded**

= --- на русском --- =

= 2.1.3 =
* fix: корректная работа автоматически генерируемых ссылок (из URL)

= 2.1.2 =
* fix: исправлено срабатывание экшена `comment_text` для тем с поддержкой html5 `comment-list`
* new: добавлены функции для ручной вставки поля загрузки изображения в шаблон формы комментрирования:
		- `the_cir_upload_field()` выводит HTML код поля вставки картинки
		- `get_cir_upload_field()` возвращает переменную с HTML кодом поля вставки картинки
* new: добавлена опция для отключения автоматического вывода поля в форме комментариев


= 2.1.1 =
* fix: исправлена ошибка в имени файла при подключении зума, и подключение файла стилей
* fix: добавлены проверки на WP_Error при загрузке файла

= 2.1.0 =
* new: добавлена возможность активировать увеличение изображения в комментарии по клику
* new: добавлена кнопка удаления картинки на странице со списокм комментариев
* new: добавлена опция для настройки размера загружаемого файла (максимальное значение ограничено настройкми `php.ini`)
* new: добавлена опция для настройки текста перед полем вставки картинки
* new: добавлена авторская ссылка (с возможностью отключить ее вывод в опциях)
* new: при окончательном удалении комментариев (очистка корзины), удаляются мета-поля с данными прикрепленного изображения
* new: при удалении изображения из медиа-библиотеки, находятся и удаляются мета-данные связанных комментариев
* fix: при импорте с _Comment Images_:
		- `get_comments()` выбирает не все комментарии, а только те, у которых были изображения;
		- не создается копия файла, а просто находится ID существующего вложения и используются его данные
		- проверяется существование файла, указанного в мета-поле `comment-image` (по `file` и по `ABSPATH + url`);
		- метаданные не записываются и картинка не переносится, если файл не был найден на диске
* fix: перед сохранением файла из его имени удаляются ненужные символы (остаются только латинские буквы, цифры, точка, подчеркивание и дефис); 
	 если после этого остается пустое имя файла - оно генерируется из номера комментария и рандомного числа (от 100 до 900)

= 2.0.3 =
* Исправлена проблема с некорректным отображением пользовательских размеров изображений в настройках плагина
* По-умолчанию, в комментарии выводится картинка со стандартным размером large

= 2.0.2 =
* Исправлено хранение данных (переход с текстового формата на сериализованый массив)

= 2.0.1 =
* Добавлено использование пользовательских размеров изображений
* Добавлена функция для корректной работы `comments_array` на всех темах
* Исправлены имена файлов
* Исправлено хранение массива данных в базе

= 2.0 =
* версия плагина Comment Images


== Upgrade Notice ==

= 2.1.3 =
This version fixes a bag with auto url feature.
