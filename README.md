
# **WEKO modules** #
**(under development)**


##Files
* repository ... WEKO module
* permalinkv2.php ... fixed some issues for first version.
* scripts ... Generate TSV file from SPASE XML file that can handle with Sword Client for WEKO(SCfW).
* [out of date]permalink.php ... Generate permalink from WEKO's open search interface.


##repository
###requires
* NetCommons 2.4.x  

###changes
* Added SPASE definition. (SPASE is a kind of metadata format)
* fixed some file because of SPASE definition.

###How to use WEKO module

1. Install this WEKO module in your NetCommons2 site.  
2. You can download SPASE metadata via OAI-PMH interface.  
Go to Item detail page, and push the OAI-PMH button.  
You will get the XML file written by SPASE metadata format.

###How to use permalinkv2.php
Developed another interface that can provide to direct file download link for the sake of developer.  
This program will makes permalink from WEKO's opensearch interface.

* permalink\_v2.php ... Used HTTP\_IF\_MODIFIED_HEADER and fixed interface 
  * usage ... http://yoursiteurl/permalink_v2.php?keyword=something  
  * If OpenSearch inteface can't find anything, then return to $BASE_URL page.  
  * If you want to get thumbnail image, then use following query  
  http://yoursiteurl/permalink_v2.php?keyword=something&thumb=on  
  

###How to use scripts
* Under development, sorry
  
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
(license free)


