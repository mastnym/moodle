$(document).ready(function() {
	$('#category_select').change(changeCatSelect);
	$('#category_select').change();
	$('#update').click(updateCat);
	$('#add').click(addRow);
	// zaregistrovat click
	
});


function changeCatSelect(){
	var leafTable=$('#leaf');
	 var notleafTable=$('#notleaf');
	 $.ajax({
		    url : 'settings_ajax.php',
		    data: {id:$(this).val(),type:"category_changed"},
		    success: function(resp){
		    	var attrs=$.parseJSON(resp);
		    	
		    	$('#perc tr').remove();
		    	var deletes=[];
		    	$.each(attrs, function(k, v) {
		    	    	if (k.startsWith("perc_cat_")){
			    	    	addRow();
			    	    	$('#'+k).val(v);
			    	    	deletes.push(k);
			    	    }
		    	    });
		    	$.each(deletes,function(k,v){
		    		delete attrs[v];
		    	});
		    	$.each(attrs, function(k, v) {
		    		//poschovavat nepotrebne
		    		if (k=="questionsInSection"){
		    	    	leafTable.show();
		    	    	notleafTable.hide();
		    	    }
		    	    if (k=="display"){
		    	    	leafTable.hide();
		    	    	notleafTable.show();
		    	    }
		    	   
		    	    //vyplnit inputy hodnotami
		    	    var input=$('input[name="'+k+'"]');
				    if (input.attr("type")=="text"){
				    	input.val(v);
				    }else if(input.attr("type")=="checkbox"){
				    	var checked=(v==1)?true:false;
				    	 input.prop('checked',checked);
				    }
		    	    
		    	   
		    	});
		    }
		});
}
function updateCat(){
	var inputs=$('#settings >div :visible input,select[id^="perc"]');
	var data={};
	data['id']=$('#category_select').val();
	data['type']='update';
	$.each(inputs,function(index,input){
		input=$(input);
		if (input.prop("tagName")=="INPUT"){
			if (input.attr("type")=="text"){
				data[input.attr("name")]=input.val();
			}
			else if(input.attr("type")=="checkbox"){
				data[input.attr("name")]=$(this).is(':checked') ? 1 : 0;
			}
		}else if (input.prop("tagName")=="SELECT"){
			data[input.attr("id")]=$(this).val();
		}
		
		
	});
	$.ajax({
	    url : 'settings_ajax.php',
	    data: data,
	    success: function(resp){
	    	$("#success_image").fadeIn().delay(2000).fadeOut();
	    	var attrs=$.parseJSON(resp);
	    	$.each(attrs, function(k, v) {
	    	    var input=$('input[name="'+k+'"]');
	    	    if (input.attr("type")=="text"){
	    	    	input.val(v);
	    	    }else if(input.attr("type")=="checkbox"){
	    	    	var checked=(v==1)?true:false;
	    	    	input.prop('checked',checked);
	    	    }
	    	});
	    }
	});	
}


function addRow(){
	var $select=$('#category_select');
	var $table=$('#perc');
	var rows=$table.find('tr').length;
	
	var temp=$('<div>');
	var $percSelect=temp.append($select.clone().attr("id",'perc_cat_'+rows).css('max-width','200px'));
	
	//vypnu vsechny kategorie ktery nemaj smysl
	var disabled=[];
	$('#categories_list li:has(ul)').each(function( index ) {
		  disabled.push($(this).attr('id'));
	});
	$.each($percSelect.find('option'),function(i,el){
		var id=$(el).attr("value");
		if (disabled.indexOf(id)!=-1){
			$(el).attr("disabled","disabled");
		}
	});
	
	var row='<tr><td>'+$percSelect.html()+'</td><td><input type="text" size="3" name="perc_'+rows+'" />%</td><td><img src="pix/cross.jpg" class="delete"/></td></tr>';
	$('#perc').append(row);
	$('.delete').click(deleteRow);
	
}
function deleteRow(eventObject){
	var $tr=$(this).closest('tr');
	$tr.remove();
}
if (typeof String.prototype.startsWith != 'function'){
	  String.prototype.startsWith = function (str){
	    return this.indexOf(str) == 0;
	  };
	}



