var DotsourceAgreementSync = Class.create();
DotsourceAgreementSync.prototype = {
    initialize : function(syncClass, groupClass)
    {
		//Holds the sync class of the html object
		this._syncClass					= syncClass;
		
		//Holds the group class
		this._groupClass				= groupClass;
		
		//Holds the agreement sync class (in normal case called id)
		this._syncRegex					= new RegExp("^" + this._syncClass + "-[0-9]+$");
		
		//Holds all element for one agreement
		this._syncElementsPerAgreement	= new Object();
        
        //Holds the in change processed agreement
        this._skipChange				= new Object();
        
        //Holds a list of id's who need to hide
        this._relatedHideElements		= new Array();
        
        //Init all elements
        $$("." + this._syncClass).each(this._initializeElement.bind(this));
    },
    
    hideElement : function(id) {
    	if ($(id)) {
    		$(id).hide();
    	}
    },
    
    showElement : function(id) {
    	if ($(id)) {
    		$(id).show();
    	}
    },
    
    _initializeElement : function(element) {
    	//Holds the element agreement class
    	var elementClass;
    	elementClass = this._getAgreementClass(element);
        
        //No agreement class found -> skip element
        if (elementClass == undefined) {
        	return;
        }
        
        //Add the element to the class agreement map
        if (typeof this._syncElementsPerAgreement[elementClass] != 'undefined') {
        	this._syncElementsPerAgreement[elementClass].push(element);
        	this._skipChange[elementClass] = false;
        } else {
        	this._syncElementsPerAgreement[elementClass] = new Array(element);
        }
        
    	//Add observer
    	Event.observe(element, 'change', this.sync.bind(this, element));
    },
    
    sync : function(element) {
    	//Holds the element agreement class
    	var elementClass;
    	elementClass = this._getAgreementClass(element);
        
        //No agreement class found or already in change process -> skip element
        if (elementClass == undefined || this._skipChange[elementClass]) {
        	return;
        }
        
        //Mark the update process for the class
        this._skipChange[elementClass] = true;
        
        //Set the type to the related elements with the same class
        this._syncElementsPerAgreement[elementClass].each(function(otherElement) {
        	if (element !== otherElement) {
        		otherElement.checked = element.checked;
        	}
        }.bind(this));
        
        //Sync the main group
        this._syncGroup(element);
        
        //Clear the flag
        this._skipChange[elementClass] = false;
    },
    
    _syncGroup : function(element) {
        //Sync the main groupClass
        var groupElement	= $(element).up('.' + this._groupClass);
        var allChecked		= false;
        
        //No group class found -> nothing to do
        if (!groupElement) {
        	return;
        }
        
        //Collect if all agreement are checked or not
        groupElement.select("." + this._syncClass).each(function(agreement){
        	if (!agreement.checked) {
        		allChecked = false;
        		throw $break;
        	} else {
        		allChecked = true;
        	}
        });
        
        //If all agreements checked we can hide all other agree group classes
        if (allChecked) {
        	$$("." + this._groupClass).each(function(agreementGroup){
            	if (groupElement !== agreementGroup) {
            		agreementGroup.hide();
            	}
        	});
        } else {
        	$$("." + this._groupClass).each(function(agreementGroup){
            	if (groupElement !== agreementGroup) {
            		agreementGroup.show();
            	}
        	});
        }
    },
    
    _getAgreementClass : function(element) {
    	var agreementclass = undefined;
    	
    	//Search for the agreement class
        $w(element.className).each(function(name, index) {
        	//Check for the agreement class
        	if (name.match(this._syncRegex)) {
        		agreementclass = name;
        		throw $break;
            }
        }.bind(this));
        
        return agreementclass;
    }
};