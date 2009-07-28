function getSelected(selectId) {
	var selectBox = document.getElementById(selectId+'_sel');
	var hiddenField = document.getElementById(selectId+'_val');

	if (selectBox && hiddenField) {
		var options = "";
		for (var i = 0; i < selectBox.options.length; i++) {
			if (selectBox.options[i].selected) {
				if (options != "") options += ',';
				options += selectBox.options[i].value;
			}
		}
		hiddenField.value = options;
	}
}

function setSelected(selectId) {
	var selectBox = document.getElementById(selectId+'_sel');
	var hiddenField = document.getElementById(selectId+'_val');

	if (selectBox && hiddenField) {
		//on vide tous les éléments sélectionné
		for (var i = 0; i < selectBox.options.length; i++) {
			selectBox.options[i].selected = false;
		}
		//récup des valeurs
		value = hiddenField.value;
		if (value!="") {
			values = value.split(',');
			//pour chaque valeur trouvé dans le champ hidden on coche la valeur correspondante dans la liste si elle existe
			for (var i = 0; i < values.length; i++) {
				var found = false;
				var j=0;
				while (j < selectBox.options.length || !found) {
					if (selectBox.options[j].value == values[i]) {
						found = true;
						selectBox.options[j].selected = true;
					}
					j+=1;
				}
			}
		}
	}
}