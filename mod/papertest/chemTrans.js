
function transformChemistry(){
	var getTextNodesIn = function(el) {
	    return $(el).find(":not(iframe)").addBack().contents().filter(function() {
	        linebreaks = /\n+/gm; // without only newlines
	    	return this.nodeType == 3 && !linebreaks.test(this.nodeValue) && (this.nodeValue).trim().length!=0;
	    });
	};

	var textNodes = getTextNodesIn("body");

	var texts = new Object();
	
	chem_id=0;
	textNodes.each(function(index,node){
		
		text=(node.nodeValue).trim();
		
		dollarFormula=/\$([^\$]+)\$/gmi;
		nodollarFormula = /([A-Z][a-z]?[0-9]+)/gm;
		
		var dollarMatch =dollarFormula.test(text);
		var nodollarMatch =nodollarFormula.test(text);
			
		if (dollarMatch || nodollarMatch){
			$(node).wrap("<span id='chem_"+chem_id+"'/>");
			texts["chem_"+chem_id]=text;
			chem_id++;
		}
	});
	if (texts.length == 0){
		return
	}
	

	
	$.ajax({
		type: "POST",
		url: "/mod/papertest/convert.php",
		data: {val:JSON.stringify(texts)},
		success: function(data, textStatus, jqXHR){
			structures=JSON.parse(data);
			for (var prop in structures) {
			    $("#"+prop).html(structures[prop]);
			}
		},
	});
	
	
	
	
}

transformChemistry();






