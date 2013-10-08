Spika-Server
============

Spika is a full-fledged messenger app under MIT license.  
For any detail please refer our web site.

http://spikaapp.com/

# Development setup


## configure Vagrant.

install required software.

- VirtualBox
- Vagrant 1.3.x+



## boot VM

boot a virtual machine on your workstation.

<pre>
git clone #{this_repo}
cd #{this_repo}
vagrant up
</pre>

## pushSrv install

Access to instller for pushSrv.

[http://127.0.0.1:8080/HookUpServer/hookup_push/installer/](http://127.0.0.1:8080/HookUpServer/hookup_push/installer/)

Complete form with default info.

- database setting
	- Host: localhost
	- Database name: spika
	- Database user name: root
	- Password: leave blank 

- lib/init.php

## create testuser

keep email and password in the result

[http://127.0.0.1:8080/HookUpServer/hookup/app_specific/spikademo/setup.php](http://127.0.0.1:8080/HookUpServer/hookup/app_specific/spikademo/setup.php)

<pre>
User for auto generate content is generated. Please change createUserHander.php with this information. AP_USER = '9rVooVDi@clover-studio.com' AP_PASS = 'HNg543QC' Failed to login.
</pre>

## test usersearch API

[http://127.0.0.1:8080/HookUpServer/hookup/searchuser.php?db=spikademo](http://127.0.0.1:8080/HookUpServer/hookup/searchuser.php?db=spikademo)

You will see something like this
<pre>
[{"_id":"3b5e45fcb4d696e39b4f5f232b0005f5","_rev":"1-47be4969ba99ad00edc6a97f873f1c49","about":"Auto pilot user","favorite_groups":[],"type":"user","contacts":[],"email":"9rVooVDi@clover-studio.com","online_status":"online","birthday":1377554400,"token_timestamp":1378467097,"max_favorite_count":10,"gender":"female","name":"Create User Test","avatar_file_id":"","max_contact_count":20,"avatar_thumb_file_id":""},{"_id":"3b5e45fcb4d696e39b4f5f232b002410","_rev":"1-f88c32f2638a50869437afe93e1fe721","about":"Auto pilot user","favorite_groups":[],"type":"user","contacts":[],"email":"sAZKdA1t@clover-studio.com","online_status":"online","birthday":1377554400,"token_timestamp":1378467097,"max_favorite_count":10,"gender":"female","name":"Create User Test","avatar_file_id":"","max_contact_count":20,"avatar_thumb_file_id":""},{"_id":"3b5e45fcb4d696e39b4f5f232b002da2","_rev":"1-8552fc69e5182b96cf17f96171efd239","about":"Auto pilot user","favorite_groups":[],"type":"user","contacts":[],"email":"WA1h51fv@clover-studio.com","online_status":"online","birthday":1377554400,"token_timestamp":1378467097,"max_favorite_count":10,"gender":"female","name":"Create User Test","avatar_file_id":"","max_contact_count":20,"avatar_thumb_file_id":""}]
</pre>
