
Parts of code are based on patrickpollet's excelent ldap plugin. Thanks

######################################################################################## 
#Plugin that sychronizes course categories(not courses!!) with external system via csv.#
########################################################################################

1)csv format:
category_idnumber,category_name,parent_id_number

2)sample csv:
F100,Faculty of Chemistry, #TOP# 
F101,Faculty of Architecture, #TOP#
D1000,Department of skyscrapers, F101


3)
-Order in csv doesn't matter
-#TOP# means top level category in moodle

4)
There are 2 possible locations for csv:
	-same server as moodle instalation (fill local path in settings)
	-another machine - http without authentication (http://domain.com/csv.csv)
					 - https with certificate authentication (https://....), specify PEM certificate a private KEY - apache has to see both
5)
You should then run local/categories_sync/cli/sync.php from cron


6)
There's no way that this plugin deletes any categories. It is build to create and update categories

7)
Feel free to modify code a enjoy the plugin .)