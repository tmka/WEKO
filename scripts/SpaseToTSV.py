# -*- coding: utf-8 -*- 

from xml.dom import minidom
import xml.etree.ElementTree as ET
import ConfigParser
import sys
import os

##global definition
settinfFile = "weko.ini"
header_ar = []
body_ar = []
argvs = sys.argv 
argc = len(argvs)


##ini_filename, extract_array
def getSetting(inifilename,attribute_ar=[]):
    inifile = ConfigParser.SafeConfigParser()
    inifile.read(inifilename)

    for key in inifile.options("WEKO"):
        return 0

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
        print "Error"
        

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

def main():

    ###Check argument
    #if (argc !=1 ):
    #    print "Error, Template: python SpasetToTSV.py TargetXML(SPASE format)"
    #    quit()

    ###ダイアログ生成
    ###Granule　もしくはそれ以外かをチェック
    print "Granule->y, others->Enter"
    input_line = raw_input()
    if (input_line == "y" or input_line == "Y"):
    #####Extract metadata from Granule file########
        doc = minidom.parse("Granule.xml")
        spase_element = doc.getElementsByTagName("ResourceID")
        for i, element in enumerate(spase_element) :
            print( "{0} : {1}".format(i, element.childNodes[0].data) )

        print "Granule Finished"
        quit()

    else:
    #####Others ######
        ##get file list from target directory
        #print "Please input CDF target directory(full path)"
        #target_dir = raw_input()
        target_dir = os.getcwd() + '\\file\\'
        FileList = getFileList(target_dir)
        print FileList
        ##Parse
        #dom = minidom.parse("DisplayData.xml")
        ##Get Metadata type(NumericalData,DisplayData,etc)
        tree = ET.parse('DisplayData.xml')
        root = tree.getroot()

        #ns = {'Spase': 'http://www.iugonet.org/data/schema'}
        print root.findall("./Spase")

        for attr in root.findall('{http://www.iugonet.org/data/schema}DisplayData'):
            for dd in attr.iter('ResourceID'):
                print(dd.attrib)
        #for dd in root.findall(".//DisplayData[2]"):
        #    print dd
        '''
        for actor in root.findall('Spase:DisplayData',ns):
            name = actor.find('Spase:ResourceHeader',ns)
            #print name.text
            for char in actor.findall('Spase:ResourceName',ns):
                print(' |-->', char.text)
        '''
        #elelist= root.findall(".//ResourceID")
        #for ele in elelist:
        #    print(ele.text);

        #if not args or len(args) > 1:
        #    print "Error, Template: python SpasetToTSV.py TargetDirectory"
        #    quit()
        ##Other data
        print "Others Finished"
        quit()
    #outputHeader(dom.documentElement)
    #printAllElement(dom.documentElement)
    
    
    
if __name__=='__main__':
    main()

    