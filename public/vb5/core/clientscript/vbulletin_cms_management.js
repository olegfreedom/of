/*======================================================================*\
|| #################################################################### ||
|| # vBulletin 5.0.5
|| # ---------------------------------------------------------------- # ||
|| # Copyright �2000-2013 vBulletin Solutions Inc. All Rights Reserved. ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
var jsAction;var curr_SectId=false;var curr_parentcat=false;var curr_catid=false;var curr_nodeid=false;var submitAction;var hiddenDiv;function applyChanges(B,A){form=document.getElementById(B);if(form!==undefined){form.action=A;form.submit()}}function setChecked(B,A){if(document.getElementById(A)!==undefined){document.getElementById(A).checked=B}}function setCategoryList(F,E,C,G,B,D,A){if(F==""||E==""||document.getElementById(F)==undefined){return }params="";if(G==""){url="../ajax.php"}else{url=G}if(B==""){B="all"}request=url+"?do=find_categories&type="+B+"&name="+E;if(D!=""){request+="&value="+D}if(A){request+="&sort=1"}categoryKeys=document.getElementsByName(C);postdata="";for(catid in categoryKeys){checkBox=document.getElementById(E+"_"+catid);if((checkBox!=undefined)&&checkBox.checked){postdata+="checkedcat[]="+catid+"&"}}load_html(F,request,postdata,null,null)}function checkTitles(A,B){msg="";if(document.getElementById("cms_node_title")!=undefined){if(document.getElementById("cms_node_title").value.length<3){msg=A+"  "}}if(document.getElementById("cms_node_url")!=undefined){if(document.getElementById("cms_node_url").value.length<3){msg=msg+B}}if(msg!=""){alert(msg);return false}return true}function setFormValue(A,B){if(document.getElementById(A)!=undefined){document.getElementById(A).value=B}else{var C=document.createElement("input");C.type="hidden";C.name=A;C.id=A;C.value=B;document.getElementById("cms_data").appendChild(C)}}function searchNodes(A,B){if(A==undefined){A="."}url=A+"/ajax.php?do=find_leaves";if(document.getElementById("contenttypeid")!=undefined&&document.getElementById("contenttypeid").value!=""){url=url+"&contenttypeid="+document.getElementById("contenttypeid").value+"&formid="+encodeURI(B)}if(document.getElementById("title_filter")!=undefined&&document.getElementById("title_filter").value!=""){url=url+"&title_filter="+encodeURI(document.getElementById("title_filter").value)}if(document.getElementById("state_filter")!=undefined&&document.getElementById("state_filter").value!=""){url=url+"&state_filter="+document.getElementById("state_filter").value}if(document.getElementById("author_filter")!=undefined&&document.getElementById("author_filter").value!=""){url=url+"&author_filter="+document.getElementById("author_filter").value}load_html("search_results",url,"",null,null)}function flagCategory(B){if(B==undefined){B=curr_parentcat}if(parseInt(B)<1){YAHOO.util.Dom.setStyle(document.getElementById("catchecked_img_0"),"display","")}else{YAHOO.util.Dom.setStyle(document.getElementById("catchecked_img_0"),"display","none")}i=1;while(document.getElementById("catedit_id_"+i)!=undefined){var A=document.getElementById("catchecked_img_"+i);if(document.getElementById("catedit_id_"+i).value==B){YAHOO.util.Dom.setStyle(A,"display","")}else{YAHOO.util.Dom.setStyle(A,"display","none")}i++}if(document.getElementById("target_categoryid")!=undefined){document.getElementById("target_categoryid").value=B}}function flagSection(A){if("filter"==submitAction){document.getElementById("sectionid").value=A;document.getElementById("cms_data").submit()}i=1;while(document.getElementById("sectedit_id_"+i)!=undefined){if(document.getElementById("sectedit_id_"+i).value==A){document.getElementById("sectchecked_img_"+i).style.display="inline";YAHOO.util.Dom.addClass("sectchecked_ul_"+i,"section_row")}else{document.getElementById("sectchecked_img_"+i).style.display="none";YAHOO.util.Dom.removeClass("sectchecked_ul_"+i,"section_row")}i++}if(document.getElementById("target_categoryid")!=undefined){document.getElementById("target_categoryid").value=false}}function setSection(C,A,B){if(B==undefined){B=-1}if(jsAction=="move_section"||jsAction=="publish_section"||jsAction=="unpublish_section"||jsAction=="move_node"||jsAction=="publish_node"||jsAction=="unpublish_node"||jsAction=="new"||jsAction=="filter_category"||jsAction=="filter_section"||jsAction=="filter_nodesection"){document.getElementById("sectionid").value=C;document.getElementById("do").value=jsAction;document.getElementById("cms_data").submit()}else{if(jsAction=="new_section"||jsAction=="save_section"){setFormValue("target_sectionid",C);return false}else{if(jsAction=="doCategory"){flagSection(C);document.getElementById("catedit_category_list").style.display="block";setFormValue("sectionid",C);setFormValue("curr_SectId",C);curr_parentcat=curr_catid;console.log("parendtcat fixed "+curr_parentcat);load_html("catedit_category_list",script_location+"/ajax.php?do=list_categories&sectionid="+C,"",null,flagCategory)}else{if(jsAction=="doSection"){flagSection(C);setFormValue("target_sectionid",C);setFormValue("sectionid",curr_SectId)}else{if(document.getElementById("sectionName")!=undefined){document.getElementById("sectionName").innerHTML=A}if(document.getElementById("sectionid")!=undefined){document.getElementById("sectionid").value=C}document.getElementById("catedit_category_list").style.display="none";return false}}}}}function checkShouldSave(D,B,C,A){form=document.getElementById(D);if(form===undefined){return false}if(!checkFormChanged(D,B)){if(A!==""){form.action=A}form.submit();return true}if(confirm(C)){if(A!==""){form.action=A}form.submit()}return false}function checkFormChanged(D,C){form=document.getElementById(D);if(form===undefined){return false}for(var B=0;B<form.elements.length;B++){element=form.elements[B];if(element.name!=C){if(element.options!==undefined){option_selected=false;for(var A=1;A<element.options.length;A++){option=element.options[A];if(option.selected!=option.defaultSelected){return true}}}else{if(element.defaultChecked!==""){if(element.defaultChecked!=element.checked){return true}}else{if(element.defaultValue!==undefined){if(element.defaultValue!=element.value){return true}}}}}}return false}function showCatEdit(E,M,C,L,A,I){var H=document.getElementById("title_editor");var F=((M==-1)&&(C==-1));var D=document.getElementById("category_selector");var B=document.getElementById("sectionid");var G=document.getElementById("catedit_category_list");var J=document.getElementById("category_title_controls");var K=document.getElementById("section_picker_controls");if(A!=""){document.getElementById("section_tab").innerHTML=A}submitAction="";jsAction="doCategory";curr_catid=C;curr_SectId=M;if(E=="filter"){url=location+"/ajax.php?do=list_nodes";load_html("title_editor",url,"",null,null);YAHOO.util.Dom.setStyle(J,"display","none");K.style.visibility="visible";YAHOO.util.Dom.setStyle(D,"display","none");YAHOO.util.Dom.setStyle(H,"width","350px");YAHOO.util.Dom.setStyle(G,"display","none")}else{YAHOO.util.Dom.setStyle(J,"display","");YAHOO.util.Dom.setStyle(H,"width","700px");YAHOO.util.Dom.setStyle(D,"display","");YAHOO.util.Dom.setStyle(G,"display","");if((E=="new")&&F){K.style.visibility="visible";H.style.visibility="visible"}else{K.style.visibility="visible";document.getElementById("catedit_category_list").style.visibility="visible"}setSection(M,A,I)}document.getElementById("title_editor").style.display="block";document.getElementById("categoryid").value=-1;document.getElementById("category_title").value=L;submitAction=E}function showSectionEdit(D,A,C,F){submitAction="";jsAction="doSection";document.getElementById("title_editor").style.display="block";var E=YAHOO.util.Dom.getRegion("title_editor");var B=YAHOO.util.Dom.get("iframeie6die");if(B){YAHOO.util.Dom.setStyle(B,"top",E.top+"px");YAHOO.util.Dom.setStyle(B,"width",E.width+"px");YAHOO.util.Dom.setStyle(B,"height",E.height+"px");YAHOO.util.Dom.setStyle(B,"right",E.right+"px");YAHOO.util.Dom.setStyle(B,"left",E.left+"px");YAHOO.util.Dom.setStyle(B,"display","block")}document.getElementById("sectionid").value=-1;document.getElementById("section_title").value=F;curr_nodeid=C;curr_SectId=A;if("new_section"==D){flagSection(C)}else{flagSection(A)}submitAction=D}function setOrder(C,A,B){document.getElementById("do").value="set_order";setFormValue("id",C);setFormValue("sectionid",A);setFormValue("displayorder",B);document.getElementById("cms_data").submit()}function confirmCategoryDelete(B,A){if(B){if(confirm(A)){document.getElementById("categoryid").value=B;document.getElementById("do").value="delete_category";document.getElementById("cms_data").submit()}}}function confirmSectionDelete(B,A){if(B){if(confirm(A)){setFormValue("delete_sectionid",B);document.getElementById("do").value="delete_section";document.getElementById("cms_data").submit()}}}function setCategory(A,B){document.getElementById("category_title").value=PHP.trim(document.getElementById("category_title").value);if(""==document.getElementById("category_title").value){alert(A);return }document.getElementById("title").value=document.getElementById("category_title").value;if("edit"==submitAction){document.getElementById("categoryid").value=curr_catid;document.getElementById("do").value="save_category";document.getElementById("cms_data").submit()}if("new"==submitAction){if(!(parseInt(curr_catid)>0||parseInt(document.getElementById("sectionid").value)>0)){alert(B);return }if(curr_catid){setFormValue("categoryid",curr_catid)}setFormValue("do","new_category");document.getElementById("cms_data").submit()}}function setEditedSection(A){document.getElementById("section_title").value=PHP.trim(document.getElementById("section_title").value);if(""==document.getElementById("section_title").value){alert(A);return }setFormValue("title",document.getElementById("section_title").value);setFormValue("sectionid",curr_SectId);setFormValue("nodeid",curr_nodeid);document.getElementById("do").value=submitAction;document.getElementById("cms_data").submit()}function swapSections(B,A){setFormValue("nodeid",B);setFormValue("nodeid2",A);document.getElementById("do").value="swap_sections";document.getElementById("cms_data").submit()}function setCategoryView(){document.getElementById("catedit_section_list").style.display="none";document.getElementById("catedit_category_list").style.display="block";document.getElementById("section_tab").style.backgroundColor="#ffffff";document.getElementById("category_tab").style.backgroundColor="#bbbbbb"}function setSectionView(){document.getElementById("catedit_section_list").style.display="block";document.getElementById("catedit_category_list").style.display="none";document.getElementById("section_tab").style.backgroundColor="#bbbbbb";document.getElementById("category_tab").style.backgroundColor="#ffffff"}function clearSectionFlags(){i=1;while(document.getElementById("sectedit_id_"+i)!=undefined){document.getElementById("sectchecked_img_"+i).style.display="none";i++}}function getSectionList(A){load_html("cms_sections_list",script_location+"/ajax.php?do=list_allsection&order="+A,"",null,null)}function showNodeWindow(B){jsAction=B;document.getElementById("sel_node_0").style.display="block";document.getElementById("sel_node_0").focus();var C=YAHOO.util.Dom.getRegion("sel_node_0");var A=YAHOO.util.Dom.get("iframeie6die");if(A){YAHOO.util.Dom.setStyle(A,"top",C.top+"px");YAHOO.util.Dom.setStyle(A,"width",C.width+"px");YAHOO.util.Dom.setStyle(A,"height",C.height+"px");YAHOO.util.Dom.setStyle(A,"right",C.right+"px");YAHOO.util.Dom.setStyle(A,"left",C.left+"px");YAHOO.util.Dom.setStyle(A,"display","block")}if(document.getElementById("title_filter")!=undefined&&document.getElementById("title_filter").value!=""){document.getElementById("title_filter").value=""}if(document.getElementById("section_search_button")!=undefined){document.getElementById("section_search_button").click()}}function deleteGrouping(B,A){if(confirm(A)){document.getElementById("do").value="delete_"+B;document.getElementById("cms_data").submit()}else{return false}}function clearSearch(){if(document.getElementById("state_filter")!=undefined){document.getElementById("state_filter").selectedIndex=-1}if(document.getElementById("title_filter")!=undefined){document.getElementById("title_filter").value=""}if(document.getElementById("author_filter")!=undefined){document.getElementById("author_filter").selectedIndex=-1}if(document.getElementById("contenttypeid")!=undefined){document.getElementById("contenttypeid").selectedIndex=-1}}function toggleSubSection(A,C,B){if(parseInt(A)>0&&document.getElementById("sect_toggle_"+A)!=undefined){sect_body=document.getElementById("detail_list").tBodies[0];if(document.getElementById("sect_toggle_"+A).innerHTML==" -"){document.getElementById("sect_toggle_"+A).innerHTML=" +";for(i=sect_body.rows.length-1;i>0;i--){if(parseInt(sect_body.rows[i].cells[0].innerHTML)==A){if(i<sect_body.rows.length-2){parent_level=parseInt(sect_body.rows[i].cells[1].innerHTML);i++;this_level=parseInt(sect_body.rows[i].cells[1].innerHTML);if(this_level==parent_level+1){while((i<sect_body.rows.length)&&parseInt(sect_body.rows[i].cells[1].innerHTML)>parent_level){sect_body.deleteRow(i)}}break}}}}else{foundit=false;for(i=1;i<sect_body.rows.length;i++){if(sect_body.rows[i].cells[0].innerHTML==A&&i<sect_body.rows.length-2){parent_level=parseInt(sect_body.rows[i].cells[1].innerHTML);i++;this_level=parseInt(sect_body.rows[i].cells[1].innerHTML);if(this_level==parent_level+1){while((i<sect_body.rows[i].length)&&parseInt(sect_body.rows[i].cells[1].innerHTML)>parent_level){sect_body.rows[i].style.display="block";i++}}break}}if(foundit){document.getElementById("sect_toggle_"+A).innerHTML=" -"}else{curr_SectId=A;load_html("hidden_content",script_location+"/ajax.php?do="+B+A+"&level="+C,"",null,addSections);document.getElementById("sect_toggle_"+A).innerHTML=" -"}}}return false}function checkUrlAvailable(A,C,B){if(A!=undefined){hiddenDiv=C;hiddenDiv.innerHTML="";load_html(hiddenDiv,getBaseUrl()+"/ajax.php?do=checkurl","url="+A.value+"&nodeid="+B,null,showDupUrl)}}function showDupUrl(){if(document.getElementById(hiddenDiv).innerHTML!=""){alert(document.getElementById(hiddenDiv).innerHTML)}}function addSections(){if(document.getElementById("hidden_content")!=undefined){xfr_body=document.getElementById("sect_xfer").tBodies[0];sect_body=document.getElementById("detail_list").tBodies[0];for(var A=1;A<sect_body.rows.length;A++){if(sect_body.rows[A].cells[0].innerHTML==curr_SectId){for(j=0;j<xfr_body.rows.length;j++){var B=sect_body.insertRow(A+j+1);B.innerHTML=xfr_body.rows[j].innerHTML}document.getElementById("sect_toggle_"+curr_SectId).innerHTML=" -";break}}}if(document.getElementById("hidden_content")!=undefined){document.getElementById("hidden_content").innerHTML=""}}function makeSEOUrl(C,D,A){var B=/[\s$+,\/:=\?@"\'<>%{}|\\^~[\]`\r\n\t\x00-\x1f\x7f]/g;document.getElementById(D).value=C.replace(B,"-").replace(/(-+)/gi,"-").replace(/(^-|-$)/gi,"");document.getElementById(A).value=C}function toggleCheckBox(B,A,C,D){for(unitId=1;unitId<=C;unitId++){switch(D){case"-1":document.getElementById(B+"_"+A+"_"+unitId.toString()).checked=!document.getElementById(B+"_"+A+"_"+unitId.toString()).checked;break;case"0":document.getElementById(B+"_"+A+"_"+unitId.toString()).checked=false;break;case"1":document.getElementById(B+"_"+A+"_"+unitId.toString()).checked=true;break;default:break}}};