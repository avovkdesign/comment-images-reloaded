=== Comment Images Reloaded ===
Contributors: wppuzzle, egolacrima
Tags: comment, image, attachment, images, comments, attachments, image zoom
Donate link: http://avovkdesign.com/bymecoffee
Requires at least: 3.2
Tested up to: 4.4.2
Stable tag: 2.1.2
License: GPLv2 or later 

The plugin allows visitors to attach their photo or other image to the comment.

== Description ==

**The plugin monitors technical issues, which reduce number of queries to the database and the size of the page with hundreds of comments.**

**CIR** ([Comment Images Reloaded](http://wp-puzzle.com/comment-images-reloaded/ "Comment Images Reloaded")) позволяет прикреплять фотографии или другие изображения к каждому комментарию. 
**Плагин учитывает технические моменты, без соблюдения которых значительно растет нагрузка на БД, а страница сайта с сотнями комментариев непомерно увеличивается в размере.**

= CIR обеспечивает: =
* получение всех данных об изображениях **всего лишь двумя запросами в БД**, не зависимо от количества комментариев на странице;
* **настройку размера выводимого в комментарии изображения** (на выбор доступны все зарегистрированные в WordPress размеры  - `thumbnail`, `medium`, `large` + пользовательские); в полном размере изображение будет выведено только в том случае, если ширина и выcота стандартных картинок выставлены в `0` в настроках сайта;
* пользовательскую настройку максимального размера для загружаемых картинок (ограничен только настройками сервера в php.ini).

= Вывод поля для вставки картинки =
Поле для вставки картинки автоматичсеки выводится после кнопки 'Отправить комментарий'. Вы можете расположить его в любом другом месте формы на свое усмотрение, используя специальные функции. Для этого нужно:
1. В настройках плагина отметить галочкой опцию _Поле вставки изображения_ (это отключает автоматичсекий вывод)
1. В своем шаблоне формы в нужном месте вызать одну из следующих функций:
	- чтобы вывести HTML код: `if (function_exists("the_cir_upload_field")) { the_cir_upload_field(); }`
	- чтобы получить переменную с HTML кодом: `if (function_exists("get_cir_upload_field")) { get_cir_upload_field(); }`


За основу CIR взят плагин - [Comment Images](https://wordpress.org/plugins/comment-images/ "Comment Images").


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



== Screenshots ==

1. The default comment form in Twenty Sixteen with image upload form.
1. Comments Dashboard showing image for each comment.
1. Admin page with plugin settings.



== Changelog ==

= 2.1.2 =

* fix: исправлен срабатывание экшена `comment_text` для тем с поддержкой html5 `comment-list`
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
* Добавлена функция для корректной работы comments_array на всех темах
* Исправлены имена файлов
* Исправлено хранение массива данных в базе

= 2.0 =
* Reloaded версия плагина Comment Images

