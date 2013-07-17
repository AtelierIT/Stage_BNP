		
	jQuery.fn.bnpGallery = function (p_width, //default width of image
								p_height, //default height of image
								p_slots,
								j_details //parameter containing images and other details, json type
								) {
		current_ind = 0;
		
		go_left = function(ind) {
			if (ind > (no_images - p_slots)) {current_ind--; return;}  //3 = numbers of slots
			
			var m_left = ind * (p_width + 10) ;
			jQuery('#ulContainerGal').css("margin-left", "-"+m_left+"px");
			
			return true;
		}
		
		go_right = function(ind) {
			if (ind < 0) {current_ind++; return;}
		
			var m_left = ind * (p_width + 10);
			jQuery('#ulContainerGal').css("margin-left", "-"+m_left+"px");
			
			return true;
		}
		
		action_image = function(param) {
							if (param) window.location.href = param;
						}
								
		var div_height  = p_height + 20;
		var arr_details = JSON.parse(j_details);
		var no_images   = arr_details.length;
		
		///////create the list with images
		var ulImage = document.createElement('ul');
		ulImage.id = "ulImageGal";
		jQuery(ulImage).addClass("bnpGalUl");
		
		for (i=0;i < no_images; i++) {
			liImage = document.createElement('li');
			jQuery(liImage).html("<img id='"+arr_details[i].id+"' src='" + arr_details[i].path + "' width='"+p_width+"px' height='"+p_height+"px' onclick='action_image(\""+arr_details[i].id+"\")' />");
			jQuery(liImage).appendTo(ulImage);
		} 
		
		//enclose in a container
		var ulContainer = document.createElement('div');
		ulContainer.id = "ulContainerGal";
		
		jQuery(ulImage).appendTo(ulContainer);
		
		////// end create list with images
		
		jQuery('<div />',
			{
				"id": "bnpGalery_divLeft",
				style : "float: left; width: 50px; height: " + div_height + "px; text-align: right; padding-top: 20px;",
				html : '&nbsp;<img src="/skin/frontend/default/bonaparte/images/ar-left.png" style="vertical-align: middle; width: 20px;" onclick="go_right(--current_ind)" />'
		}).appendTo(jQuery(this));
		
		jQuery('<div />',
			{
				"id": "bnpGalery_divContent",
				style : "float: left; width: "+(p_width * p_slots + 10 * p_slots + 10 )+"px; height: "+p_height+"px; overflow: hidden; display: block;",
				html : ""
		}).appendTo(jQuery(this));
		
		jQuery(ulContainer).appendTo(jQuery('#bnpGalery_divContent'));
		
		jQuery('<div />',
			{
				"id": "bnpGalery_divRight",
				style : "float: left; width: 50px; height: " + div_height + "px;",
				html : '<img src="/skin/frontend/default/bonaparte/images/ar-right.png" style="vertical-align: middle; width: 20px;  padding-top: 20px;" onclick="go_left(++current_ind)" /> &nbsp;'
		}).appendTo(jQuery(this));
		
	}
