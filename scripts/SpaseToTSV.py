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
        namespaces = {'Spase': 'http://www.iugonet.org/data/schema'} # add more as needed
        tree = ET.parse('NumericalData.xml')
        root = tree.getroot()
        #print root.findall('Spase:DisplayData',namespaces)



        print "#####test0######"
        namespace = "{http://www.iugonet.org/data/schema}"
        tag_list = []
        #tag_val = root.tag.replace(namespace,"") + "." ## Spase
        tag_val = ""
        tag_stack = "" ##save tag history
        #tag_val = "" 
        iter_ = tree.getiterator()
        ccc = root.getchildren()

        for elem in iter_:
            #print (elem.tag, elem.text)
            #check用に改行コードなどを置換
            check_val = ''
            check_val += elem.text.replace('\n','')
            check_val += elem.text.replace('\t','')
            check_val += elem.text.replace('\r','')
            #print "check_val = " + check_val
            check_val = check_val.strip()
            #print "check_val = " + check_val
            ##Tagのみで値を含まないなら、タグを保管
            if (len(check_val) == 0):
                tag_val += str(elem.tag).replace(namespace,"") + "."
            ##テキストを含むなら、出力
            else:
                #tag_val = tag_val.rstrip(".") #delete .
                print "%s = %s" % (tag_val, elem.text)



        print "#####test1######"
        namespace = "{http://www.iugonet.org/data/schema}"
        tag_list = []
        #tag_val = root.tag.replace(namespace,"") + "." ## Spase
        tag_val = ""
        tag_stack = "" ##save tag history
        #tag_val = "" 
        iter_ = tree.getiterator()
        ccc = root.getchildren()
        ##iterator for XML
        count = 0
        for elem in iter_:
            #tag_list.append(elem)
            if(count > 1):
                break
            else:
                count = count + 1
            for i,app in enumerate(elem):
                if(len(tag_list) > 0):
                    while(len(tag_list) > 0):
                        tag_list.pop()
                tag_val += str(app.tag).replace(namespace,"") + "."
                #もし要素が1個も無ければadd
                if len(tag_list) < 1:
                    tag_list.append(str(app.tag).replace(namespace,""))
                #要素が多ければ、削除
                else:
                    while(len(tag_list) < 1):
                        print ("taglen =" + len(tag_list))
                        tag_list.pop()
                if app.text.isspace() != True:
                    print "paragraph1"
                    #print "before tag_val = " + tag_val
                    #print str(app.tag).replace(namespace,"")
                    #print "tag_val[-1:]" + tag_val[-1:]
                    while(tag_val[-1:] == "."):
                                tag_val = tag_val.rstrip(".") #delete .
                    tag_list_val = ""
                    for i in range(len(tag_list)):
                        tag_list_val += str(tag_list[i]) + "."
                    tag_list_val = tag_list_val.rstrip(".") #delete .

                    print "%s = %s" % (tag_list_val, app.text)
                    tag_list.pop()
                    tag_val = tag_val.replace(str(app.tag).replace(namespace,""),"")
                    #print "after tag_val = " + tag_val
                    #print "app.tag = " + str(app.tag).replace(namespace,"")
                else:
                    for j,app2 in enumerate(app):
                        if(len(tag_list) > 1):
                            while(len(tag_list) > 1):
                                tag_list.pop()
                        if(tag_val.find(str(app2.tag).replace(namespace,"")) == True):
                            #delete tag
                            tag_val = tag_val.replace(str(app2.tag).replace(namespace,""),"")
                        else:
                            tag_val += str(app2.tag).replace(namespace,"") + "."

                        if len(tag_list) < 2:
                            tag_list.append(str(app2.tag).replace(namespace,""))
                        else:
                            while(len(tag_list) < 2):
                                print ("taglen =" + len(tag_list))
                                tag_list.pop()
                        if app2.text.isspace() != True:
                            print "paragraph2"
                            #print "Second paragraph"
                            while(tag_val[-1:] == "."):
                                tag_val = tag_val.rstrip(".") #delete .

                            tag_list_val = ""
                            for i in range(len(tag_list)):
                                tag_list_val += str(tag_list[i]) + "."
                            tag_list_val = tag_list_val.rstrip(".") #delete .

                            print "%s = %s" % (tag_list_val, app2.text)
                            tag_list.pop()
                            
                            tag_val = tag_val.replace(str(app2.tag).replace(namespace,""),"")
                        else:
                            for k,app3 in enumerate(app2):
                                if(len(tag_list) > 2):
                                    while(len(tag_list) > 2):
                                        tag_list.pop()
                                #別のタグに移動した際の処理をするべき
                                if(tag_val.find(str(app3.tag).replace(namespace,"")) != -1):
                                    #delete tag
                                    print "Found same tag in paragraph3"
                                    tag_val = tag_val.replace(str(app3.tag).replace(namespace,""),"")
                                else:
                                    tag_val += str(app3.tag).replace(namespace,"") + "."

                                if len(tag_list) < 3:
                                    tag_list.append(str(app3.tag).replace(namespace,""))
                                    #print "added tag_list" + tag_list[len(tag_list)-1]
                                else:
                                    while(len(tag_list) < 3):
                                        print ("taglen =" + len(tag_list))
                                        tag_list.pop()

                                if app3.text.isspace() != True:
                                    print "paragraph3"
                                    #print "Third paragraph"
                                    while(tag_val[-1:] == "."):
                                        tag_val = tag_val.rstrip(".") #delete .

                                    tag_list_val = ""
                                    for i in range(len(tag_list)):
                                        tag_list_val += str(tag_list[i]) + "."
                                    tag_list_val = tag_list_val.rstrip(".") #delete .
                                    print "%s = %s" % (tag_list_val, app3.text)
                                    tag_list.pop()
                                    tag_val = tag_val.replace(str(app3.tag).replace(namespace,""),"")
                                else:
                                    for l,app4 in enumerate(app3):
                                        if(len(tag_list) > 3):
                                            while(len(tag_list) > 3):
                                                tag_list.pop()
                                        tag_val += str(app4.tag).replace(namespace,"") + "."
                                        if len(tag_list) < 4:
                                            tag_list.append(str(app4.tag).replace(namespace,""))
                                        else:
                                            while(len(tag_list) < 4):
                                                print ("taglen =" + len(tag_list))
                                                tag_list.pop()
                                        if app4.text.isspace() != True:
                                            print "paragraph4"
                                            #print "Third paragraph"
                                            while(tag_val[-1:] == "."):
                                                tag_val = tag_val.rstrip(".") #delete .
                                            tag_list_val = ""
                                            for i in range(len(tag_list)):
                                                tag_list_val += str(tag_list[i]) + "."
                                            tag_list_val = tag_list_val.rstrip(".") #delete .

                                            print "%s = %s" % (tag_list_val, app4.text)
                                            tag_list.pop()
                                            tag_val = tag_val.replace(str(app4.tag).replace(namespace,""),"")
                                            #tag_val = tag_val.replace(str(app3.tag).replace(namespace,""),"")
                                        else:
                                            print "finished part4"
                                            continue
        print "tag = " + tag_val

        print "#####test2######"
        ###イテレータ 3段階までの深さに対応
        ###version 1.0.3 の項目が上手く出力されない
        appointments = root.getchildren()
        tag_val = ""
        for appointment in appointments:
            appt_children = appointment.getchildren()
            tag_val += str(appointment.tag).replace(namespace,"") + "."
            for appt_child in appt_children:
                tag_val += str(appt_child.tag).replace(namespace,"") + "."
                #print "tag=" + appt_child.tag.replace(namespace,"")
                if appt_child.text.isspace() != True:
                    print "%s=%s" % (tag_val, appt_child.text)
                    tag_val = str(tag_val).replace(namespace,"")
                else:
                    appt_children2 = appt_child.getchildren()
                    for appt_child2 in appt_children2:
                        if appt_child2.text.isspace() != True:
                            print "%s=%s" % (tag_val, appt_child2.text)
                        else:
                            tag_val += str(elem.tag).replace(namespace,"") + "."
                            appt_children3 = appt_child2.getchildren()
                            for appt_child3 in appt_children3:
                                if appt_child3.text.isspace() != True:
                                    print "%s=%s" % (tag_val, appt_child3.text)
        print "tag = " + tag_val


        print "#####test3######"
        for child in root:
            for child2 in child:
                print (child2.tag,child2.attrib)


        for attr in root.findall('{http://www.iugonet.org/data/schema}NumericalData'):
            for dd in attr.iter('ResourceID'):
                print(dd.attrib)
        #for dd in root.findall(".//DisplayData[2]"):
        #    print dd
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

    