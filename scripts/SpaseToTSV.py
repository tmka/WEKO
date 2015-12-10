# -*- coding: utf-8 -*- 

from xml.dom import minidom
import xml.etree.ElementTree as ET
import ConfigParser
import csv
import sys
import os
import datetime

##global definition
settinfFile = "weko.ini"
resourcename = ""
header_ar = []
body_ar = []
MetadataPath = ""
argvs = sys.argv 
argc = len(argvs)


##ini_filename, extract_array
def getSetting(inifilename,attribute_ar=[]):
    inifile = ConfigParser.SafeConfigParser()
    inifile.read(inifilename)
    for key in inifile.options("WEKO"):
        return 0

#設定ファイルから必須要素を読み出す
def getSettingAttribute(filename,att_ar,body_ar):
    #iniファイルを指定パスから読み出し
    inifile = ConfigParser.SafeConfigParser()
    inifile.read(filename)
    dic = {}
    #iniファイルから全ての項目を読み出す
    for section in inifile.sections():
        #一時的に辞書配列に格納した後、優先度順で並び替える
        dic[section] = inifile.get(section,"number")
    #i = Attribute , j = number(優先順位)
    for i,j in sorted(dic.items(), key=lambda x:x[1]):
        att_ar.append(i)
        body_ar.append(inifile.get(i,"data"))
    return None

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

#Granule版Attribute
#file_ar ファイル一覧 body_ar 本文
def WriteGranule(file_ar,body_ar,metadata_type="",suffix_name=''):
    f=csv.writer(file(MetadataPath,'a'),lineterminator='\n',delimiter='\t')
    todaydetail  =    datetime.datetime.today()
    time = todaydetail.strftime("%Y/%m/%d")
    for i,m in enumerate(file_ar):
        writetext = []
        #print m
        for j,n in enumerate(body_ar):
            if (j==0):
                writetext.append(body_ar[j] + "."+ metadata_type)
            elif(n == "filename"):
                writetext.append(m)
            elif(n == "picture"):
                writetext.append(m + ".png")
            elif(n=="time"):
                writetext.append(time)
            else:
                writetext.append(body_ar[j])
        f.writerow(writetext)
    return None

def WriteOtherSpaseMetadata(body_ar,metadata_type="",resource_name="",suffix_name=''):
    f=csv.writer(file(MetadataPath,'a'),lineterminator='\n',delimiter='\t')
    todaydetail  =    datetime.datetime.today()
    time = todaydetail.strftime("%Y/%m/%d")
    writetext = []
    for j,n in enumerate(body_ar):
        if (j==0): #item_type
            writetext.append(body_ar[j] + "."+ metadata_type)
        elif(n == "filename"):
            writetext.append(resource_name)
        elif(n=="time"):
            writetext.append(time)
        else:
            writetext.append(body_ar[j])
    f.writerow(writetext)
    return None

##HeaderをTSVに書き出す
def WriteHeader(ar,suffix_name=''):
    f=csv.writer(file(MetadataPath,'w'),lineterminator='\n',delimiter='\t')
    f.writerow(ar)


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


def checkSpaseMetadata(filename):
    tree = ET.parse(filename)
    root = tree.getroot()
    iter_ = tree.getiterator()
    namespace = "{http://www.iugonet.org/data/schema}"

    for elem in iter_:
        for i,app in enumerate(elem):
            if(i ==1):
                return str(app.tag).replace(namespace,"")
                quit()

def extractAllSpaseMetadata(filename, attribute_ar=[], value_ar=[]):
    tree = ET.parse(filename)
    root = tree.getroot()
    namespace = "{http://www.iugonet.org/data/schema}"
    tag_list = []
    tag_val = ""
    iter_ = tree.getiterator()
    ##iterator for XML
    count = 0
    #深さ4にまで対応
    for elem in iter_:
        #just once
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
                while(tag_val[-1:] == "."):
                            tag_val = tag_val.rstrip(".") #delete .
                tag_list_val = ""
                for i in range(len(tag_list)):
                    tag_list_val += str(tag_list[i]) + "."
                tag_list_val = tag_list_val.rstrip(".") #delete .
                #print "%s = %s" % (tag_list_val, app.text)
                attribute_ar.append(tag_list_val)
                value_ar.append(app.text)
                tag_list.pop()
                tag_val = tag_val.replace(str(app.tag).replace(namespace,""),"")
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
                        while(tag_val[-1:] == "."):
                            tag_val = tag_val.rstrip(".") #delete .

                        tag_list_val = ""
                        for i in range(len(tag_list)):
                            tag_list_val += str(tag_list[i]) + "."
                        tag_list_val = tag_list_val.rstrip(".") #delete .
                        #print "%s = %s" % (tag_list_val, app2.text)
                        attribute_ar.append(tag_list_val)
                        value_ar.append(app2.text)
                        tag_list.pop()
                        
                        tag_val = tag_val.replace(str(app2.tag).replace(namespace,""),"")
                    else:
                        for k,app3 in enumerate(app2):
                            if(len(tag_list) > 2):
                                while(len(tag_list) > 2):
                                    tag_list.pop()
                            if(tag_val.find(str(app3.tag).replace(namespace,"")) != -1):
                                print "Found same tag in paragraph3"
                                tag_val = tag_val.replace(str(app3.tag).replace(namespace,""),"")
                            else:
                                tag_val += str(app3.tag).replace(namespace,"") + "."
                            if len(tag_list) < 3:
                                tag_list.append(str(app3.tag).replace(namespace,""))
                            else:
                                while(len(tag_list) < 3):
                                    print ("taglen =" + len(tag_list))
                                    tag_list.pop()

                            if app3.text.isspace() != True:
                                while(tag_val[-1:] == "."):
                                    tag_val = tag_val.rstrip(".") #delete .

                                tag_list_val = ""
                                for i in range(len(tag_list)):
                                    tag_list_val += str(tag_list[i]) + "."
                                tag_list_val = tag_list_val.rstrip(".") #delete .
                                #print "%s = %s" % (tag_list_val, app3.text)
                                attribute_ar.append(tag_list_val)
                                value_ar.append(app3.text)
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
                                        while(tag_val[-1:] == "."):
                                            tag_val = tag_val.rstrip(".") #delete .
                                        tag_list_val = ""
                                        for i in range(len(tag_list)):
                                            tag_list_val += str(tag_list[i]) + "."
                                        tag_list_val = tag_list_val.rstrip(".") #delete .
                                        #print "%s = %s" % (tag_list_val, app4.text)
                                        attribute_ar.append(tag_list_val)
                                        value_ar.append(app4.text)
                                        tag_list.pop()
                                        tag_val = tag_val.replace(str(app4.tag).replace(namespace,""),"")
                                    else:
                                        print "finished part4"
                                        continue

def main():
    global MetadataPath
    global resourcename

    ###Check argument
    ###ダイアログ生成
    if (argc != 2):
        print 'Usage: python %s Spase.xml' % argvs[0]
        quit()

    Metadata_type = checkSpaseMetadata(argvs[1])
    print "Metadata_Type=" + Metadata_type

    if (Metadata_type == 'Granule'):
    #####Extract metadata from Granule file########
        ##get file list from target directory
        #print "Please input CDF target directory(full path)"
        #target_dir = raw_input()


        ##WEKO attributes
        getSettingAttribute('weko.ini', header_ar, body_ar)
        ##Spase attributes
        extractAllSpaseMetadata(argvs[1],header_ar,body_ar)

        for i,val in enumerate(header_ar):
            if (header_ar[i].find('Version') != -1):
                del header_ar[i]
                del body_ar[i]
            if (header_ar[i].find('ResourceName') != -1): #get resourcename as a title(WEKO title)
                resourcename = body_ar[i]

        ##get file list from target directory
        target_dir = os.getcwd() + '\\file\\'
        FileList = getFileList(target_dir)
        if (len(FileList) == 0):
            print "There are no files!"
            quit()

        MetadataPath = os.getcwd() + "\\" + argvs[1].replace(".xml",".tsv")

        WriteHeader(header_ar)
        WriteGranule(FileList,body_ar,Metadata_type)

        print "Outputted Metadata to " + MetadataPath

        print "Granule Finished"
        quit()

    else:
    #####Others ######
        
        ##Personの場合、全てPerson.tsvになるので分けたほうが良い
        ##WEKO attributes
        getSettingAttribute('weko.ini', header_ar, body_ar)
        ##Spase attributes
        extractAllSpaseMetadata(argvs[1],header_ar,body_ar)
        for i,val in enumerate(header_ar):
            print header_ar[i]
            if (header_ar[i].find('File') != -1):
                del header_ar[i]
                del body_ar[i]
                #Thumbnailも同時に消す
            if (header_ar[i].find('Thumbnail') != -1):
                del header_ar[i] 
                del body_ar[i]
            if (header_ar[i].find('Version') != -1):
                del header_ar[i]
                del body_ar[i]
            if (header_ar[i].find('ResourceName') != -1): #get resourcename as a title(WEKO title)
                resourcename = body_ar[i] 
            if (header_ar[i].find('PersonName') != -1): #get Personame (for Person.xml)
                resourcename = body_ar[i]  

        MetadataPath = os.getcwd() + "\\" + argvs[1].replace(".xml",".tsv")
        WriteHeader(header_ar)
        WriteOtherSpaseMetadata(body_ar,Metadata_type,resourcename)

        print "Outputted Metadata to " + MetadataPath
        print "Others Finished"
        quit()
    
if __name__=='__main__':
    main()

    