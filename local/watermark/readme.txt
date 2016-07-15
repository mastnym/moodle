1) Make sure you have imagemagick a pdftk installed and you moodle is in VCS
2) In dataroot of moodle, create a "watermark" directory and place a watermark.png there
3) Add this to mod/resource/view:



		$pdf_watermark= get_config('local_watermark');
		if ($file->get_mimetype()=="application/pdf" 
			&& isset($pdf_watermark->enabled) 
			&& $pdf_watermark->enabled==1 
			&& ($pdf_watermark->courses=="" || in_array($resource->course, explode(";",$pdf_watermark->courses)))){
			
			require_once '../../local/watermark/locallib.php';
			$filerecord=watermark_stampPDF($file);
			if ($filerecord){
				watermark_process_view($file,$filerecord,$resource,$cm,$course,$redirect,$displaytype);
			}
		die;	
		}
	
	
	on line 89 , right before:
		
		if ($redirect) {
		    // coming from course page or url index page
		    // this redirect trick solves caching problems when tracking views ;-)
		    $path = '/'.$context->id.'/mod_resource/content/'.$resource->revision.$file->get_filepath().$file->get_filename();
		    $fullurl = moodle_url::make_file_url('/pluginfile.php', $path, $displaytype == RESOURCELIB_DISPLAY_DOWNLOAD);
		    redirect($fullurl);
		}
	
4)Add 
	
		$forcedownload=optional_param('forcedownload', 1, PARAM_BOOL);
	
	to the top of /draftfile.php right after
	
		$preview = optional_param('preview', null, PARAM_ALPHANUM);
	
	and change last row from:
		send_stored_file($file, 0, false, true, array('preview' => $preview)); // force download - security first!
	to:
		send_stored_file($file, 0, false, $forcedownload, array('preview' => $preview)); // force download - security first!

5) Copy watermark/ to /local/
6) Login and setup config and verify any PDF