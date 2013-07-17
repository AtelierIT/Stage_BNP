/**
 * The main file is from magento bus this version is simplifyed.
 */
FormDependences = Class.create();
FormDependences.prototype = {
    /**
     * Structure of elements: {
     *     'id_of_dependent_element' : {
     *         'id_of_master_element_1' : 'reference_value',
     *         'id_of_master_element_2' : 'reference_value'
     *         ...
     *     }
     * }
     * @param object elementsMap
     * @param object config
     */
    initialize : function (elementsMap)
    {
        for (var idTo in elementsMap) {
            for (var idFrom in elementsMap[idTo]) {
                if ($(idFrom)) {
                    Event.observe($(idFrom), 'change', this.trackChange.bindAsEventListener(this, idTo, elementsMap[idTo]));
                    this.trackChange(null, idTo, elementsMap[idTo]);
                } else {
                    this.trackChange(null, idTo, elementsMap[idTo]);
                }
            }
        }
    },


    /**
     * Define whether target element should be toggled and show/hide its row
     *
     * @param object e - event
     * @param string idTo - id of target element
     * @param valuesFrom - ids of master elements and reference values
     * @return
     */
    trackChange : function(e, idTo, valuesFrom)
    {
        // define whether the target should show up
        var shouldShowUp = true;
        for (var idFrom in valuesFrom) {
            var from = $(idFrom);
            if (!from) {
            		shouldShowUp = false;
            } else if (typeof(valuesFrom[idFrom]) == 'object') {
            	//Negate the assumption
            	shouldShowUp = false;
            	
            	//Search the value in the array
            	for (var i = 0; i < valuesFrom[idFrom].length; i++) {
            		if (from.value == valuesFrom[idFrom][i]) {
            			shouldShowUp = true;
            			break;
            		}
            	}
            } else if(from.value != valuesFrom[idFrom]) {
                shouldShowUp = false;
            }
        }

        // toggle target row
        if (shouldShowUp) {
            $(idTo).select('input', 'select').each(function (item) {
                if (!item.type || item.type != 'hidden') {
                    item.disabled = false;
                }
            });
            $(idTo).show();
        } else {
            $(idTo).select('input', 'select').each(function (item){
                if (!item.type || item.type != 'hidden') {
                    item.disabled = true;
                }
            });
            $(idTo).hide();
        }
    }
}