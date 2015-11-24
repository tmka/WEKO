# -*- coding: utf-8 -*- 

import xml.dom.minidom
import ConfigParser
import sys


##global definition
settinfFile = "weko.ini"
header_ar = []
body_ar = []


##ini_filename, extract_array
def getSetting(inifilename,attribute_ar=[]):
    inifile = ConfigParser.SafeConfigParser()
    inifile.read(inifilename)

    for key in inifile.options("WEKO"):


    ##このやり方ではなくforでやるべき
    #attribute_ar.appedn(inifile.get("type","value"))

#ファイル一覧を取得(*.cdfのみ)
#dirpath 対象先のディレクトリ
def getFileList(dirpath):
    filelist = []
    for root, dirs, files in os.walk(dirpath):
        for file in files:
            if os.path.splitext(file)[1] == u'.cdf':
                filelist.append(file)
    filelist.sort(cmp=None, key=None, reverse=False)
    return filelist


##HeaderをTSVに書き出す
def WriteHeader(ar,suffix_name=""):
    CSVfilename = os.getcwd() + MetadataPath.replace(".tsv","") + str(suffix_name) + ".tsv"
    print CSVfilename
    f=csv.writer(file(CSVfilename,'w'),lineterminator='\n',delimiter='\t')
    f.writerow(ar)
    #csvf.close()
    print "Wrote Header"
    return None

#Output header
#WEKO基本要素+SPASE node
def outputHeader(node,tagname=""):
    ######WEKO基本要素#########


    #######SPASE Header#########
    # エレメントノードの場合はタグ名を表示する
    if node.nodeType == node.ELEMENT_NODE:
        if node.tagName != 'Spase' or node.tagName != 'Version':
            sys.stdout.write("{0}.".format(node.tagName))
            # 再帰呼び出し
        for child in node.childNodes:
            outputHeader(child, node.tagName)
    elif node.nodeType in [node.TEXT_NODE, node.COMMENT_NODE]:
        #print ""
        return 0
    else:
        print "a"
        

    # テキストもしくはコメントだった場合dataを表示する
    ##elif node.nodeType in [node.TEXT_NODE, node.COMMENT_NODE]:
        # スペースを取り除く
        ##data = node.data.replace(' ', '')
        # 改行のみではなかった時のみ表示する
        ##if data!='\n': sys.stdout.write("{0}".format(node.data))

#Output body
def printAllElement(node, tagname=""):
    space = ''
    #for i in range(hierarchy*4):
    #    space += ' '
    
    # エレメントノードの場合はタグ名を表示する
    if node.nodeType == node.ELEMENT_NODE:
        sys.stdout.write("{0}.".format(node.tagName))
        # 再帰呼び出し
        for child in node.childNodes:
            printAllElement(child, node.tagName)
    # テキストもしくはコメントだった場合dataを表示する
    elif node.nodeType in [node.TEXT_NODE, node.COMMENT_NODE]:
        # スペースを取り除く
        data = node.data.replace(' ', '')
        # 改行のみではなかった時のみ表示する
        if data!='\n': sys.stdout.write("{0}".format(node.data))




###main
###ダイアログ生成
###Granule　もしくはそれ以外

dom = xml.dom.minidom.parse("DisplayData.xml")

outputHeader(dom.documentElement)
#printAllElement(dom.documentElement)


    