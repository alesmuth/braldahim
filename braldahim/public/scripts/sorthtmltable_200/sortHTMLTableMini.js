
var cellIndex;String.prototype.trim=function(str){str=this!=window?this:str;return str.replace(/^[\s\xA0]+/g,'').replace(/[\s\xA0]+$/g,'');};String.prototype.removeaccent=function(str){str=this!=window?this:str;return str.replace(/[àáâãäå]/g,'a').replace(/[èéêë]/g,'e').replace(/[ìíîï]/g,'i').replace(/[òóôõö]/g,'o').replace(/ñ/g,'n').replace(/[ùúûü]/g,'u').replace(/[ýÿ]/g,'y').replace(/ç/g,'c').replace(/œ/g,'oe');};Array.prototype.ismember=function(search){for(var elem in this)if(this[elem]==search)return elem;return-1;};var HTMLTableTools={};HTMLTableTools=Class.create();HTMLTableTools.prototype={initialize:function(tableId){this.version='2.0';this.dateVersion='16-01-2006';Object.extend(this.options={pathToImgs:'/public/images/',imgUp:'fleche_haut.png',imgUpActive:'fleche_haut_active.png',imgDown:'fleche_bas.png',imgDownActive:'fleche_bas_active.png',skipCells:[],multiColSort:true,reorderCol:false,showSortIndex:true,highlight:true,highlightColumn:false,selectRow:true,multipleSelect:false},arguments[1]||{});if(!this.options.highlight){this.options.highlightColumn=false;this.options.selectRow=false;this.options.multipleSelect=false;}
this.error=false
this.errorMsg='no error.';if(!document.getElementById){this.error=true;this.errorMsg="Navigateur incompatible";return;}
if(typeof tableId=='undefined'){this.error=true;this.errorMsg="Veuillez fournir l'ID du tableau à gérer.";return;};this.table=$(tableId);if(!this.table){this.error=true;this.errorMsg="Le tableau ayant pour ID "+tableId+" est introuvable.";return;}
var currentTRClass='rowA';for(var rowCpt=1;rowCpt<this.table.rows.length;rowCpt++){Element.addClassName(this.table.rows[rowCpt],currentTRClass);if(currentTRClass=='rowA')currentTRClass='rowB';else currentTRClass='rowA';}
this.jsTable=new Array();for(var rowCpt=1;rowCpt<this.table.rows.length;rowCpt++){this.jsTable[rowCpt-1]=new Array();}
this.headerTable=new Array();this.sortFunction=new Array();var dataType='none';var cellContent='';var totalCells=this.table.rows[0].cells.length;for(var cellCpt=0;cellCpt<this.table.rows[0].cells.length;cellCpt++){for(var rowCpt=1;rowCpt<this.table.rows.length;rowCpt++){if(!this.table.rows[rowCpt].cells[cellCpt].innerHTML){this.table.rows[rowCpt].cells[cellCpt].innerHTML='&nbsp';}
var cellContent=this.table.rows[rowCpt].cells[cellCpt].innerHTML.unescapeHTML().trim();if((cellContent!=''))break;}
this.sortFunction[cellCpt]=new Array();if(cellContent.match(/^\d\d[\/-]\d\d[\/-]\d\d\d\d$/)){this.sortFunction[cellCpt]["ASC"]=this._sortStringASC;this.sortFunction[cellCpt]["DESC"]=this._sortStringDESC;dataType='date';}else if(cellContent.match(/^[0-9$£fF\.\s-]+$/)){this.sortFunction[cellCpt]["ASC"]=this._sortNumberASC;this.sortFunction[cellCpt]["DESC"]=this._sortNumberDESC;dataType='number';}else{this.sortFunction[cellCpt]["ASC"]=this._sortStringASC;this.sortFunction[cellCpt]["DESC"]=this._sortStringDESC;dataType='string';}
this.sortFunction[cellCpt]["LAST"]=false;this.sortFunction[cellCpt]["INDEX"]=0;for(var rowCpt=1;rowCpt<this.table.rows.length;rowCpt++){if(!this.table.rows[rowCpt].cells[cellCpt].innerHTML){this.table.rows[rowCpt].cells[cellCpt].innerHTML='&nbsp';}
cellContent=this.table.rows[rowCpt].cells[cellCpt].innerHTML.unescapeHTML();switch(dataType){case'date':{this.jsTable[rowCpt-1][cellCpt]=new Date(cellContent.substring(6),cellContent.substring(3,5)-1,cellContent.substring(0,2));break;}
case'number':{this.jsTable[rowCpt-1][cellCpt]=parseFloat(cellContent.replace(/[^0-9.-]/g,''));if(isNaN(this.jsTable[rowCpt-1][cellCpt]))this.jsTable[rowCpt-1][cellCpt]=0;break;}
case'string':{this.jsTable[rowCpt-1][cellCpt]=cellContent.toLowerCase();break;}}
this.jsTable[rowCpt-1][cellCpt+totalCells]=this.table.rows[rowCpt].cells[cellCpt].firstChild;if($(this.table.rows[rowCpt]).id){this.jsTable[rowCpt-1][cellCpt+(totalCells*2)]=$(this.table.rows[rowCpt]).id;}}}
var imgUp=new Array();var imgDown=new Array();imgUp[0]=Builder.node('img',{id:this.table.id+'up0',src:this.options.pathToImgs+this.options.imgUp,style:'cursor: pointer',className:'imgStatus'});imgDown[0]=Builder.node('img',{id:this.table.id+'down0',src:this.options.pathToImgs+this.options.imgDown,style:'cursor: pointer',className:'imgStatus'});var spanIndex=new Array();spanIndex[0]=Builder.node('span',{id:this.table.id+'span0',style:'position: absolute; display: none;',className:'sortIndex'});spanIndex[0].innerHTML='';for(var cellCpt=0;cellCpt<this.table.rows[0].cells.length;cellCpt++){var skipCell=false;for(var cpt=0;cpt<this.options.skipCells.length;cpt++){if(this.options.skipCells[cpt]-1==cellCpt){skipCell=true;break;}}
if(!skipCell){if(cellCpt>0){imgUp[cellCpt]=imgUp[0].cloneNode(true);imgUp[cellCpt].id=this.table.id+'up'+cellCpt;imgDown[cellCpt]=imgDown[0].cloneNode(true);imgDown[cellCpt].id=this.table.id+'down'+cellCpt;spanIndex[cellCpt]=spanIndex[0].cloneNode(true);spanIndex[cellCpt].id=this.table.id+'span'+cellCpt;}
this.table.rows[0].cells[cellCpt].appendChild(imgUp[cellCpt]);this.table.rows[0].cells[cellCpt].appendChild(imgDown[cellCpt]);this.table.rows[0].cells[cellCpt].appendChild(spanIndex[cellCpt]);}}
for(var cellCpt=0;cellCpt<this.table.rows[0].cells.length;cellCpt++){var skipCell=false;for(var cpt=0;cpt<this.options.skipCells.length;cpt++){if(this.options.skipCells[cpt]-1==cellCpt){skipCell=true;break;}}
if(!skipCell){var elementUp=$(imgUp[cellCpt].id);var elementDown=$(imgDown[cellCpt].id);Event.observe(elementUp,'click',this.sortTable.bindAsEventListener(this),false);Event.observe(elementDown,'click',this.sortTable.bindAsEventListener(this),false);}
this.headerTable[cellCpt]=this.table.rows[0].cells[cellCpt];}
if(this.options.highlight){var allCells=this.table.getElementsByTagName('td');for(var cpt=0;cpt<allCells.length;cpt++){Event.observe(allCells[cpt],'mouseover',this.highlight.bindAsEventListener(this),false);Event.observe(allCells[cpt],'mouseout',this.downlight.bindAsEventListener(this),false);if(this.options.selectRow){this.selectedRows=new Array();Event.observe(allCells[cpt],'click',this.selectRow.bindAsEventListener(this),false);}}}},sortTable:function(e){var element=Event.element(e);if(!element)return;if(!this.options.multiColSort){for(var cellCpt=0;cellCpt<this.table.rows[0].cells.length;cellCpt++){if(this.sortFunction[cellCpt]["LAST"]){this.sortFunction[cellCpt]["LAST"]=false;$(this.table.id+'up'+cellCpt).src=this.options.pathToImgs+this.options.imgUp;$(this.table.id+'down'+cellCpt).src=this.options.pathToImgs+this.options.imgDown;}
this.sortFunction[cellCpt]["INDEX"]=0;}}
if(element.id.indexOf('up')!=-1){var sortOrder='ASC';cellIndex=parseInt(element.id.substr(this.table.id.length+2));element.src=this.options.pathToImgs+this.options.imgUpActive;element.nextSibling.src=this.options.pathToImgs+this.options.imgDown;var annul=this.sortFunction[cellIndex]['LAST']==this.sortFunction[cellIndex][sortOrder];if(annul)element.src=this.options.pathToImgs+this.options.imgUp;}else{var sortOrder='DESC';cellIndex=parseInt(element.id.substr(this.table.id.length+4));element.src=this.options.pathToImgs+this.options.imgDownActive;element.previousSibling.src=this.options.pathToImgs+this.options.imgUp;var annul=this.sortFunction[cellIndex]['LAST']==this.sortFunction[cellIndex][sortOrder];if(annul)element.src=this.options.pathToImgs+this.options.imgDown;}
var prevIndex=this.sortFunction[cellIndex]['INDEX'];if((prevIndex==0)||annul){var nmax=0;for(var cellCpt=0;cellCpt<this.table.rows[0].cells.length;cellCpt++){var n=this.sortFunction[cellCpt]['INDEX'];var nmax=n>nmax?n:nmax;if((n>0)&&(n>prevIndex)&&(prevIndex!=0)){n--;this.sortFunction[cellCpt]['INDEX']=n;}}
if(prevIndex==0)nmax++;this.sortFunction[cellIndex]['INDEX']=nmax;}
if(this.sortFunction[cellIndex]['LAST']==this.sortFunction[cellIndex][sortOrder]){this.sortFunction[cellIndex]['LAST']=false;this.sortFunction[cellIndex]['INDEX']=0;if(typeof this.table.rows[0].cells[cellIndex].lastChild.id!='undefined'){this.table.rows[0].cells[cellIndex].lastChild.innerHTML='';Element.hide(this.table.rows[0].cells[cellIndex].lastChild);}}
else this.sortFunction[cellIndex]['LAST']=this.sortFunction[cellIndex][sortOrder];sortedCol=new Array();var sortedColreorder=new Array();for(var cellCpt=0;cellCpt<this.table.rows[0].cells.length;cellCpt++){var n=this.sortFunction[cellCpt]['INDEX'];if(n>0){sortedCol[n-1]=new Array();sortedCol[n-1][0]=cellCpt;sortedCol[n-1][1]=this.sortFunction[cellCpt]['LAST'];sortedColreorder[n-1]=cellCpt;}}
var totalCells=this.table.rows[0].cells.length;for(var cellCpt=0;cellCpt<totalCells;cellCpt++){for(var rowCpt=1;rowCpt<this.table.rows.length;rowCpt++){var currentRow=$(this.table.rows[rowCpt]);if(currentRow.className&&Element.hasClassName(currentRow,'selectedRow')){this.jsTable[rowCpt-1][cellCpt+(totalCells*3)]='selectedRow ';}else{this.jsTable[rowCpt-1][cellCpt+(totalCells*3)]='';}}}
for(var rowCpt=0;rowCpt<this.table.rows.length;rowCpt++){var currentRow=$(this.table.rows[rowCpt]);if(currentRow.className&&Element.hasClassName(currentRow,'selectedRow')){Element.removeClassName(currentRow,'selectedRow');}}
if(sortedCol.length>0){cellIndex1=sortedCol[0][0];this.jsTable.sort(this.sortFunction[cellIndex1]['LAST']);}else if(!this.options.reorderCol)return true;var nbcell=this.table.rows[0].cells.length;for(var cellCpt=0;cellCpt<nbcell;cellCpt++){if(this.options.reorderCol){if(sortedColreorder.length<nbcell){if(sortedColreorder.ismember(cellCpt)==-1)sortedColreorder.push(cellCpt);}
var destcellCpt=sortedColreorder[cellCpt];if(this.table.rows[0].cells[cellCpt])
this.table.rows[0].cells[cellCpt].parentNode.replaceChild(this.headerTable[destcellCpt],this.table.rows[0].cells[cellCpt]);else this.table.rows[0].cells[0].parentNode.appendChild(this.headerTable[destcellCpt]);}
else var destcellCpt=cellCpt;if(this.options.showSortIndex){var nbstr=this.sortFunction[destcellCpt]['INDEX']||'';if(typeof this.table.rows[0].cells[cellCpt].lastChild.id!='undefined'){(nbstr=='')?Element.hide(this.table.rows[0].cells[cellCpt].lastChild):Element.show(this.table.rows[0].cells[cellCpt].lastChild);this.table.rows[0].cells[cellCpt].lastChild.innerHTML=nbstr;}}
for(var rowCpt=1;rowCpt<this.table.rows.length;rowCpt++){this.table.rows[rowCpt].cells[cellCpt].appendChild(this.jsTable[rowCpt-1][destcellCpt+totalCells]);var currentRow=$(this.table.rows[rowCpt]);currentRow.id=this.jsTable[rowCpt-1][destcellCpt+(totalCells*2)];if(cellCpt==0&&this.jsTable[rowCpt-1][destcellCpt+(totalCells*3)]!=''){Element.addClassName(currentRow,this.jsTable[rowCpt-1][destcellCpt+(totalCells*3)]);}}}},_sortNumberASC:function(a,b,colIndex,n){colIndex=typeof(colIndex)=='number'?colIndex:cellIndex1;n=n||0;var test=(a[colIndex]-b[colIndex]);if((test==0)&&(n<sortedCol.length))return(sortedCol[n][1](a,b,sortedCol[n][0],n+1));else return test;},_sortStringASC:function(a,b,colIndex,n){colIndex=typeof(colIndex)=='number'?colIndex:cellIndex1;n=n||0;if(a[colIndex]<b[colIndex])return-1;else if(a[colIndex]>b[colIndex])return 1;else if(n<sortedCol.length)return(sortedCol[n][1](a,b,sortedCol[n][0],n+1));else return 0;},_sortNumberDESC:function(a,b,colIndex,n){colIndex=typeof(colIndex)=='number'?colIndex:cellIndex1;n=n||0;var test=(b[colIndex]-a[colIndex]);if((test==0)&&(n<sortedCol.length))return(sortedCol[n][1](a,b,sortedCol[n][0],n+1));else return test;},_sortStringDESC:function(a,b,colIndex,n){colIndex=typeof(colIndex)=='number'?colIndex:cellIndex1;n=n||0;if(a[colIndex]<b[colIndex])return 1;else if(a[colIndex]>b[colIndex])return-1;else if(n<sortedCol.length)return(sortedCol[n][1](a,b,sortedCol[n][0],n+1));else return 0;},highlight:function(e){var element=window.event?window.event.srcElement:e?e.target:null;if(!element)return;element=this._ascendDOM(element,'td');if(!element)return;var parentRow=this._ascendDOM(element,'tr');if(!parentRow)return;if(!Element.hasClassName(parentRow,'selectedRow')){Element.addClassName(parentRow,'highlighted');}
if(this.options.highlightColumn){var cellIndex=-1;for(var cellCpt=0;cellCpt<parentRow.cells.length;cellCpt++){if(element===parentRow.cells[cellCpt])cellIndex=cellCpt;}
if(cellIndex==-1)return;for(var rowCpt=0;rowCpt<this.table.rows.length;rowCpt++){var cell=this.table.rows[rowCpt].cells[cellIndex];Element.addClassName(cell,'highlighted');}}},downlight:function(e){var element=window.event?window.event.srcElement:e?e.target:null;if(!element)return;element=this._ascendDOM(element,'td');if(!element)return;var parentRow=this._ascendDOM(element,'tr');if(!parentRow)return;parentRow.className=parentRow.className.replace(/\b ?highlighted\b/,'');if(this.options.highlightColumn){cellIndex=element.cellIndex;for(var rowCpt=0;rowCpt<this.table.rows.length;rowCpt++){var cell=this.table.rows[rowCpt].cells[cellIndex];cell.className=cell.className.replace(/\b ?highlighted\b/,'');}}},selectRow:function(e){var element=window.event?window.event.srcElement:e?e.target:null;if(!element)return;element=this._ascendDOM(element,'td');if(!element)return;var parentRow=this._ascendDOM(element,'tr');if(!parentRow)return;if(!Element.hasClassName(parentRow,'selectedRow')){if(!this.options.multipleSelect){var rows=this.table.getElementsByTagName('tr');for(var rowCpt=0;rowCpt<rows.length;rowCpt++){if(Element.hasClassName(rows[rowCpt],'selectedRow')){rows[rowCpt].className=rows[rowCpt].className.replace(/\b ?selectedRow\b/,'');}}
this.selectedRows=new Array();}
this.selectedRows.push(parentRow.id);Element.addClassName(parentRow,'selectedRow');}else{var tmpArray=this.selectedRows;this.selectedRows=new Array();for(var cpt=0;cpt<tmpArray.length;cpt++){if(tmpArray[cpt]!=parentRow.id)this.selectedRows.push(tmpArray[cpt]);}
parentRow.className=parentRow.className.replace(/\b ?selectedRow\b/,'');}
this.selectedValues=this.selectedRows.join('|');this.p_kelchoix.value=this.selectedValues;},_ascendDOM:function(element,target){while(element.nodeName.toLowerCase()!=target&&element.nodeName.toLowerCase()!='html'){element=element.parentNode;}
return(element.nodeName.toLowerCase()=='html'?null:element);}}