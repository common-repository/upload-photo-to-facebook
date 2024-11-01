jQuery(function(){
	jQuery("#frmuptf").validate();

	jQuery("#uptf_source").change(function(){
	    if( this.files[0].size > allowedFileSize )
	    {
	    	alert("exceeds the maximum upload size for this site.");
	    }
	});
});