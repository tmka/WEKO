#WEKO


##About
# **This repository is under development.** #


##Files
* repository ... WEKO module
* permalink.php ... Generate permalink from WEKO's open search interface.


##repository
You have to prepare NetCommons2 Site before you use this WEKO modules.
This WEKO modules is added following point.

* Added SPASE definition. (SPASE is a kind of metadata format)

###Usage

1. Install this WEKO module in your NetCommons2 site.  
2. You can download SPASE metadata via OAI-PMH interface.  
Go to Item detail page, and push the OAI-PMH button.  
You will get the XML file written by SPASE metadata format.

##permalink.php
Make permalink from WEKO's opensearch interface, using PHP modules.

* permalink.php(out of date) ... create permalink by WEKO's open search interface.
  * usage ... http://any.com/permalink_v2.php?keyword=something
* permalink\_v2.php ... Used HTTP\_IF\_MODIFIED_HEADER and fixed interface 
  * usage ... http://any.com/permalink_v2.php?keyword=something  
  * If OpenSearch inteface can't find anything, then return to $BASE_URL page.  
  * If you want to get thumbnail image, then use following query  
  http://any.com/permalink_v2.php?keyword=something&thumb=on  
  


##Reference
* [NetCommons2](http://www.netcommons.org/)
* [WEKO](http://weko.at.nii.ac.jp/)
* [SPASE](http://www.spase-group.org/)

##License
Copyright (c) 2007 - 2015, National Institute of Informatics  
Research and Development Center for Scientific Information Resources  

This program is licensed under a Creative Commons BSD Licence  
http://creativecommons.org/licenses/BSD/

Some source programs were edited by T.M.


