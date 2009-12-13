// PageInfo

var combolistarray= new Array();
var combolisttimerarray= new Array();
var combolistsearcharray= new Array();

// makes combolist disappear after 3 seconds 

function combolistfade(comboid) {
  if (combolistarray[comboid]!='wait') {
		document.getElementById('cl_res_'+comboid).style.display='none';
		combolistsearcharray[comboid]='';
  } else {
  	combolisttimerarray[comboid]=setTimeout("combolistfade('"+comboid+"');",3000);
	}
}

function combolistkeyup(pagesize,idwidget,prefix,callbacks,eventdata,table,labelField,numField,whereField,orderBy,search,fields) {
	if (eventdata<14||eventdata>=32 ) {
		if (!(prefix+idwidget in combolistsearcharray)) {combolistsearcharray[prefix+idwidget]='';}; 
		if (combolistsearcharray[prefix+idwidget].length==0 || eventdata==13) {
			combolistsearcharray[prefix+idwidget]=document.getElementById('cl_i'+prefix+idwidget).value;
			combolistarray[prefix+idwidget]='wait';
			document.getElementById('cl_logo_'+prefix+idwidget).className='wait';
			//ShowRoom.showlightbox=false;
			document.xfm.mfdt_eventdata.value=eventdata;
			document.xfm.mfdt_pagesize.value=pagesize;
			document.xfm.mfdt_mode.value=2;
			document.xfm.mfdt_callbacks.value=callbacks;
			document.xfm.mfdt_cmd.value='combolist';
			document.xfm.mfdt_prefix.value=prefix;
			document.xfm.mfdt_code.value=idwidget;
			document.xfm.mfdt_fields.value=fields;
			document.xfm.mfdt_table.value=table;
			document.xfm.mfdt_labelField.value=labelField;
			document.xfm.mfdt_numField.value=numField;
			document.xfm.mfdt_whereField.value=whereField;
			document.xfm.mfdt_orderBy.value=orderBy;
			if (search) {
				document.xfm.mfdt_data.value=document.getElementById('cl_i'+prefix+idwidget).value;
			} else {
				document.xfm.mfdt_data.value='';
			}
			document.xfm.mfdt_page.value=1;
			tx_metafeeditprocessFormData(xajax.getFormValues('xfm'));
		};
	};
}

function combolistfireback(comboid,pagesize,idwidget,prefix,callbacks,eventdata,table,labelField,numField,whereField,orderBy,fields) {
	if(combolistsearcharray[comboid]!=document.getElementById('cl_i'+comboid).value) {
		combolistsearcharray[comboid]=document.getElementById('cl_i'+comboid).value;
		combolistarray[comboid]='wait';
		document.getElementById('cl_logo_'+prefix+idwidget).className='wait';
		document.xfm.mfdt_eventdata.value=eventdata;
		document.xfm.mfdt_pagesize.value=pagesize;
		document.xfm.mfdt_mode.value=2;
		document.xfm.mfdt_callbacks.value=callbacks;
		document.xfm.mfdt_cmd.value='combolist';
		document.xfm.mfdt_prefix.value=prefix;
		document.xfm.mfdt_code.value=idwidget;
		document.xfm.mfdt_table.value=table;
		document.xfm.mfdt_labelField.value=labelField;
		document.xfm.mfdt_fields.value=fields;
		document.xfm.mfdt_numField.value=numField;
		document.xfm.mfdt_whereField.value=whereField;
		document.xfm.mfdt_orderBy.value=orderBy;
		document.xfm.mfdt_data.value=combolistsearcharray[comboid];
		document.xfm.mfdt_page.value=1;
		tx_metafeeditprocessFormData(xajax.getFormValues('xfm'));
	} else {
		combolistsearcharray[comboid]='';
	};
}

function combolistmouseover(comboid) {
	combolistarray[comboid]='wait';
	if (document.getElementById('cl_i'+comboid).value.length>0) document.getElementById('cl_res_'+comboid).style.display='block';
    clearTimeout(combolisttimerarray[comboid]);
}

function arrowclick(comboid,pagesize,idwidget,prefix,callbacks,eventdata,table,labelField,numField,whereField,orderBy,fields) {
	combolistarray[comboid]='wait';
	document.getElementById('cl_res_'+comboid).style.display='block';
    clearTimeout(combolisttimerarray[comboid]);
	combolistkeyup(pagesize,idwidget,prefix,callbacks,eventdata,table,labelField,numField,whereField,orderBy,1,fields);
}

function combolistmouseout(comboid) {
    combolistarray[comboid]='out';
    combolisttimerarray[comboid]=setTimeout("combolistfade('"+comboid+"');",3000);
}

function combolistdraw(json) {
	var jdata=eval('('+json+')');
	var linkarray=new Array();
	var linkcount=0;
	var res=document.getElementById('cl_res_'+jdata.prefix+jdata.idwidget);
	combolistarray[jdata.prefix+jdata.idwidget]='wait';
	var d=document.createElement("div");
	var t=document.createElement("table");
	t.className='tx-metafeedit-editmenu-list-table';
	d.appendChild(t);	
	var nbl=jdata.ls.length;	
	var nbr=jdata.rs.length;
	if (nbr==0) {
		res.innerHTML='';
		res.style.display='none';
	} else {
		// header

		var nbc=jdata.cbs.length;
		var c=nbc;
		var callbacks='';
		for (i=0;i<nbc; i++) {
			callbacks=callbacks+jdata.cbs[i].id;
			if (i<(nbc-1)) callbacks=callbacks+',';
		}
		var trh=document.createElement("tr");
		trh.className='tx-metafeedit-editmenu-list-table-header';
		t.appendChild(trh);
		if (jdata.nbpages>0) {
			var td=document.createElement("th");
			trh.appendChild(td);
			if (parseInt(jdata.page)>1) {
				var a=document.createElement("a");
				a.href="#";
				a.id="link"+jdata.prefix+jdata.idwidget+linkcount;
				linkarray[a.id]="ajxcall('"+callbacks+"','"+jdata.prefix+"','"+jdata.idwidget+"','"+jdata.pagesize+"',"+(parseInt(jdata.page)-1)+");";
				linkcount++;
				td.appendChild(a);
				txt1=document.createTextNode('<<');
				a.appendChild(txt1);
			} else {
				var txt3=document.createTextNode(' ');
				td.appendChild(txt3);						
			}
			
			var td=document.createElement("th");
			//td.colspan=nbl-2;
			td.align='center';
			trh.appendChild(td);
			if (jdata.nbpages>1) {
				var txt2=document.createTextNode('Page '+jdata.page+' sur '+jdata.nbpages);
				td.appendChild(txt2);
			} else {
				var txt2=document.createTextNode(' ');
				td.appendChild(txt2);
			}

			var td=document.createElement("th");
			td.align='right';
			trh.appendChild(td);
			if (jdata.nbpages>0 && parseInt(jdata.page) < parseInt(jdata.nbpages)) {
				var a=document.createElement("a");
				a.href="#";
				a.id="link"+jdata.prefix+jdata.idwidget+linkcount;
				linkarray[a.id]="ajxcall('"+callbacks+"','"+jdata.prefix+"','"+jdata.idwidget+"','"+jdata.pagesize+"',"+(parseInt(jdata.page)+1)+");";
				linkcount++;
				td.appendChild(a);
				txt3=document.createTextNode('>>');
				a.appendChild(txt3);
			} else {
				var txt3=document.createTextNode(' ');
				td.appendChild(txt3);			
			}
		}		

		// libelles ...
		var tr=document.createElement("tr");
		tr.className='tx-metafeedit-editmenu-list-table-header';
		t.appendChild(tr);	
		
		for (i=0;i<nbl; i++) {
			lib=jdata.ls[i].l;
			var td=document.createElement("th");
			tr.appendChild(td);
			if (i==nbl-1) td.colSpan=2;
			txt1=document.createTextNode(lib);
			td.appendChild(txt1);
		}
		// Données 
		var rowc=1;
		for (i=0;i<nbr; i++) {
			var tr=document.createElement("tr");
			tr.className='tx-metafeedit-list-row-'+rowc;
			rowc++;
			if (rowc>2) rowc=1;
			r=jdata.rs[i];
			if (r.s) {
				//alert(r.s);
				tr.style.background=r.s;
			}
			t.appendChild(tr);
		    for (j=0;j<nbl; j++) {
				var td=document.createElement("td");
				if (j==nbl-1) td.colSpan=2;
		    	tr.appendChild(td);
				var a=document.createElement("a");
		    	td.appendChild(a);
		    	var ltxt=r["i"+j];
		  		txt=document.createTextNode(ltxt);
		  		a.href='#';
		    	a.appendChild(txt);
					a.id="link"+jdata.prefix+jdata.idwidget+linkcount;
					linkarray[a.id]="document.xfm.mfdt_cmd.value='combolist';document.xfm.mfdt_code.value='"+jdata.idwidget+"';document.xfm.mfdt_prefix.value='"+jdata.prefix+"';document.xfm.mfdt_data.value='"+r.id+"';document.xfm.mfdt_tdata.value='"+r.d+"';document.xfm.mfdt_mode.value=3;tx_metafeeditprocessFormData(xajax.getFormValues('xfm'));";
					linkcount++;
		    }
	  }
	  //alert('donnees');

	  // footer
		var trh=document.createElement("tr");
		trh.className='tx-metafeedit-editmenu-list-table-header';
		t.appendChild(trh);
		if (jdata.nbpages>0) {
			var td=document.createElement("th");
			trh.appendChild(td);
			if (parseInt(jdata.page)>1) {
				var a=document.createElement("a");
				a.href="#";
				a.id="link"+jdata.prefix+jdata.idwidget+linkcount;
				linkarray[a.id]="ajxcall('"+callbacks+"','"+jdata.prefix+"','"+jdata.idwidget+"','"+jdata.pagesize+"',"+(parseInt(jdata.page)-1)+");";
				linkcount++;
				td.appendChild(a);
				txt1=document.createTextNode('<<');
				a.appendChild(txt1);
			} else {
				var txt3=document.createTextNode(' ');
				td.appendChild(txt3);			
			}			
			var td=document.createElement("th");
			td.colspan=nbl-2;
			td.align='center';
			trh.appendChild(td);
			if (jdata.nbpages>1) {
				var txt2=document.createTextNode('Page '+jdata.page+' sur '+jdata.nbpages);
				td.appendChild(txt2);
			}

			var td=document.createElement("th");
			td.align='right';
			trh.appendChild(td);
			if (jdata.nbpages>0 && parseInt(jdata.page) < parseInt(jdata.nbpages)) {
				var a=document.createElement("a");
				a.href="#";
				a.id="link"+jdata.prefix+jdata.idwidget+linkcount;
				linkarray[a.id]="ajxcall('"+callbacks+"','"+jdata.prefix+"','"+jdata.idwidget+"','"+jdata.pagesize+"',"+(parseInt(jdata.page)+1)+");";
				linkcount++;
				td.appendChild(a);
				txt3=document.createTextNode('>>');
				a.appendChild(txt3);
			} else {
				var txt3=document.createTextNode(' ');
				td.appendChild(txt3);			
			}
		}		
		res.innerHTML=d.innerHTML
		// link setup for firefox
		
		
		//nbl=linkarray.length;
		for (i=0;i<linkcount;i++) {
			var el=document.getElementById("link"+jdata.prefix+jdata.idwidget+i);
			var fobj=new Function('event',linkarray["link"+jdata.prefix+jdata.idwidget+i]+"if(event.preventDefault){event.preventDefault();};event.returnValue = false;");
			addEvent(el,'click',fobj);	
		}
		res.style.display='block';
	}
}

function addEvent(obj, evt, func){
	if (/safari/i.test(navigator.userAgent) && evt == "dblclick") {
		obj.ondblclick = func;
	}else if (window.addEventListener){
		obj.addEventListener(evt, func, false);
	}else if (window.attachEvent){
		obj.attachEvent("on" + evt, func);
	}
}

function ajxcall(callbacks,prefix,idwidget,pagesize,page) {
		document.getElementById('cl_logo_'+prefix+idwidget).className='wait';
		document.xfm.mfdt_mode.value=2;
		document.xfm.mfdt_callbacks.value=callbacks;
		document.xfm.mfdt_cmd.value='combolist';
		document.xfm.mfdt_prefix.value=prefix;
		document.xfm.mfdt_code.value=idwidget;
		document.xfm.mfdt_pagesize.value=pagesize;
		document.xfm.mfdt_eventdata.value='';
		document.xfm.mfdt_data.value=document.getElementById('cl_i'+prefix+idwidget).value;
		document.xfm.mfdt_page.value=page;
		tx_metafeeditprocessFormData(xajax.getFormValues('xfm'));
}

function showLightbox() {}
function hideLightbox() {}
