<?php
error_reporting(0);
$ajax=$_GET['ajax']==1;
$imgId=$_GET['id'];
$img=$error=$response='';
$path='images/';//Image folder
$user=1;//user id could be user id from database stored in a session array
//Database credentials
$db_host='localhost';
$db_name='test_image';
$db_pass='junior';
$db_user='root';
$db_port='3306';
$conn=new PDO('mysql:host='.$db_host.';port='.$db_port.';dbname='.$db_name,$db_user,$db_pass,array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES => false));//connect to database
if($ajax){//Check for ajax request
	header('Content-Type: '.$imgId?'text/plain':'application/json'.'; charset=UTF-8');//Send appropriate header with ajax request
	if(isset($imgId)){//Request to delete image
		$imgId=preg_replace('/[^0-9]+/', '', $imgId);//replace non integers if present in image id
		$image=$conn->prepare("SELECT name,id FROM images WHERE id=$imgId");//Check for the existence of image to be deleted
		$image->execute();
		$image_=$image->fetchAll();
		if(sizeof($image_)>0){
			$image->closeCursor();
			foreach($image_ as $_img){
				$imgId=$_img['id'];
				$del=$conn->exec("DELETE FROM images WHERE id=$imgId");//Delete image info from db
			    if($del!==false&&$del>0){
			    	if(@unlink("$path$imgId$_img[name]")){//Remove image from folder
		    		    $response='delete';//Text to check for client side
		    	    }
		    	}
			}
			
		}
		
	}
}
if(isset($_FILES['photo'])){//Request to upload photo
	$file=$_FILES['photo'];
	$temp=$file['tmp_name'];
	if(is_uploaded_file($temp)&&$file['error']==UPLOAD_ERR_OK){//Make sure file was uploaded from post method without errors
		$filename=basename($file['name']);//Get filename only and not (pontentially) filepath
		$extension=explode('.', $filename);
		$extension=end($extension);//Get file meme type
		$supported=array('png','jpg','jpeg','gif');//Array of supported image file types
		$filesize=500000;//Max file size
		if(in_array($extension, $supported)){//Check for supported file types
			if(!($file['size']>$filesize)){//Check file size
				$new_img=$conn->prepare("INSERT INTO images (name,user) VALUES (?,$user)");//Create new db entry
			    $new_img->execute(array($filename));
				$imgId=$conn->lastInsertId();//Get unique image id
				$new_img->closeCursor();
				//Create file path with db id apart of file path
				//Id make file unique in folder and is also used to location file at 
				//a later date
				$destination="$path$imgId$filename";
				//Move file to image folder with id apart of file name
				if(!move_uploaded_file($temp, $destination)){
					//delete database entry as file upload was unsuccessful
					$del=$conn->exec("DELETE FROM images WHERE id=$imgId");
		                        if($del!==false&&$del>0){
		            	            $error='Sorry, unable to upload file.';
					}
				}
			}else{
				$error='Sorry, image size can be no greater than '.($filesize/1000).'kb.';
			}
		}else{
			$error='Sorry, only '.implode(', ', $supported).' file types are supported.';
		}
	}
    if($ajax){//Check for ajax request
    	$response='{';//Start json
		if($error){//Error
			$response.='"error":"'.$error.'"';
		}else{//File uploaded successfully
			$response.='"src":"/'.$destination.'"';//Add new file path to json response
			//Add new file id to response
			//File id will be used if user wants to delete file
			$response.=',"id":'.$imgId;
		}
		$response.='}';//End json
    }
}
if($ajax){//Check if request was done vi ajax
    if($response){//Check for and return response
		echo $response;
	}
	exit;//end execution of script
}
?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Upload Image</title>
		<style>
		    *{
		    	box-sizing:border-box;
		        -moz-box-sizing:border-box;
		        -webkit-box-sizing:border-box;
		    }
		    html{
		    	font-size:16px;
		    }
		    body,div,img,span,form,h2{
		    	padding:0;
		    	border:0;
		    	margin:0;
		    	font: inherit;
	            vertical-align: baseline;
		    }
		    body,div,img,span,form{
		    	font-size: 100%;
		    }
		    #main-container{
		    	height:400px;
		    	margin:0 auto;
		    }
			#dropbox{
				padding:10px;
				background:#e0e0e0;
			}
			.width{
				width:400px;
			}
			#btn-container{
				height:40px;
				overflow:hidden;
				position:relative;
			}
			body{
				text-align: center;
				overflow:hidden;
			}
			#progress{
				height:25px;
				background:#ffffff;
				border:1px solid #e0e0e0;
				z-index: 4;
				top:45%;
				left:0; 
                right:0; 
                margin-left:auto; 
                margin-right:auto; 
            }
			#remove-img{
				background: url('/images/icons/remove-x.png') no-repeat top left;
				top:4px;
				right:4px;
				display:inline-block;
				width:16px;
				height:16px;
				cursor: pointer;
			}
			#screen{
				z-index: 3;
				background: #000;
				opacity: 0.8;
				filter:alpha(opacity=80);
				top:-10000px;
				left:-10000px;
				right:-10000px;
				bottom:-10000px;
			}
			.abs-pos{
				position:absolute;
			}
			.rel-pos{
				position:relative;
			}
			.hide{
				display:none;
			}
			button{
				display:block;
				background:rgb(0,128,0);
				color:#fff;
				border:1px solid rgb(0,128,0);
				font-weight:bold;
			}
			.max{
				height:100%;
				width: 100%;
			}
			.top-margin{
				margin-top:20px !important;
			}
			input[type="file"]{
				position:absolute;
				top:0;
				right:0;
				opacity: 0;
				filter:alpha(opacity=0);
				z-index: 10;
				cursor:pointer;
			}
			form{
				text-align:center;
			}
			@media(max-width:480px){
				body{
					font-size: 0.8em;
				}
				.width{
					width:200px;
				  
				}
				#main-container{
					  height:200px;
				}
				#progress{
					height:18px;
				}
				
			}
			@media(max-width:767px){
				body{
					font-size: 0.9em;
				}
				.width{
					width:300px;
				    
				}
				#main-container{
					height:300px;
				}
				#progress{
					height:20px;
				}
				
			}
		</style>
	</head>
	<body class="rel-pos">
		<div id="progress" class="width hide abs-pos">
				<div id="current-progress" style="background:blue;height:100%;width:0;"></div>
		    </div>
		<div id="screen" class="hide abs-pos"></div>
		<?php
		//Check for file upload errors if file wasn't uploaded asynchronously
		if($error){
			echo '<div style="color:red;padding:4px;">'.$error.'</div>';
		}
		//Check db for image entry
		foreach($conn->query("SELECT id,name FROM images WHERE user=$user") as $image) {
			$img='/';	
			$imgId=$image['id'];
			$img.=$path;
			$img.=$imgId;
			$img.=$image['name'];
		}
		//Hide upload container if image was alreay uploaded
		?>
		<h2>Upload an Image</h2>
		<div id="main-container" class="top-margin width rel-pos">
			<div id="img-container" class="rel-pos max" <?php if(!$img){ echo ' style="display:none;"';}?>>
				<span id="remove-img" class="abs-pos"></span>
				<img id="img" src="<?php if($img){ echo $img;}?>" width="100%" height="100%" alt="">
			</div>
			<form id="dropbox" class="max" method="post" enctype="multipart/form-data" action="/upload_file.php"<?php if($img){ echo ' style="display:none;"';};?>>
			   <div>
			   	 <div id="drag-text" class="hide">Drag & drop your file here <br>or</div>
				 <div id="btn-container" title="Choose File" class="top-margin">
					<button type="button" class="max">Upload File</button>
				    <input type="file" name="photo" accept="image/*" id="selectFile" class="max"/>
			     </div>
				</div>
			</form>
		</div>
		<script type="text/javascript">
			<!--
			var doc = document,
			win=window,
			xhr,imgId,
			url='/upload_file.php?ajax=1',//Ajax url
			dropBox = doc.getElementById('dropbox'),//Upload/drag and drop area
			removeImg = doc.getElementById('remove-img'),//Remove image element
			imgContainer = doc.getElementById('img-container'),//Uploaded image container
			img = doc.getElementById('img'),//Image tag
			div = doc.createElement('div');//Create generic div that will be used to check for drag and drop support
			if(('draggable' in div) || ('ondragstart' in div && 'ondrop' in div)){//Check drag and drop support and add appropriate listeners
				doc.getElementById('drag-text').style.display='block';//Show text informing user of drag and drop support
			    dropBox.style.border = '3px dotted green';//Add green border to drop box
				dropBox.ondragover = dropBox.ondragenter = function(e){
					e.stopPropagation();
                    e.preventDefault();
                    this.style.borderColor='red';//Red border 
               }
               dropBox.ondragleave  = function(e){
					e.stopPropagation();
                    e.preventDefault();
                    this.style.borderColor='green';//Default green border 
               }
				dropBox.ondrop = function(e){
					e.stopPropagation();
                    e.preventDefault();
                    uploadFile(e.dataTransfer.files);//Call process drop file with drop file as argument
			    }
			}
			doc.getElementById('selectFile').onchange = function(e){//File selected conventionally
				if(typeof (win['FormData'])!='undefined'){//Check if file can be uploaded asynchronously
					uploadFile(this.files);//Call process drop file with selected file as argument
					dropBox.reset();//Reset form
				}else{//submit form manually
					dropBox.submit();
				}
			}
			function ajaxReq(){//Ajax object
				return win.XMLHttpRequest?new XMLHttpRequest():new ActiveXObject('Microsoft.XMLHTTP');
			}
			doc.getElementById('remove-img').onclick = function(e){//Remove existing image via ajax
		       e.stopPropagation();
		       xhr = ajaxReq();
		       xhr.open('GET',url + '&id=' + imgId,true);//Add image id to url
			   xhr.onreadystatechange = function(){
			   	if(xhr.readyState == 4 && xhr.status == 200){
			   		if(xhr.response.trim()=='delete'){//Image has been deleted with return of 'delete' text
			   			imgContainer.style.display='none';//Hide image container
					    dropBox.style.display='block';//Show drop/select image container
					    img.src='';//Set img tag src to empty
					}
			    }
			   }
			   xhr.send();
			}
			function uploadFile(file){//Process file upload 
			    for(var i= 0;i < file.length; i++){
			    	var name=file[0].name,
			    	supportedMemes=['png','jpg','gif','jpeg'],//Supported image types
			    	memeType=name.split('.');//Create array of file type
			    	memeType=memeType[memeType.length-1];//Get file type
			    	if(supportedMemes.indexOf(memeType)!='-1'){//Check for acceptable file types
			    		//Upload file with FormData() constructor
			    		var formData=new FormData(),//Instantiate form data constructor
                        progress=doc.getElementById('progress'),//Upload progress container
			    		screeen=doc.getElementById('screen'),//Black screen
			    		currentProgress=doc.getElementById('current-progress');//Current progress div
			    		currentProgress.style.width='0';//Current progress div width starts at zero
			    		xhr = ajaxReq();
			    		screeen.style.display='block';//Set black screen 
			    		progress.style.display='block';//Set progress bar atop black screen 
			    		formData.append('photo',file[0],name);
			    	    xhr.open('POST',url,true);
			    	    xhr.onprogress = function(e){
			    	    	if (e.lengthComputable){
			    	    		//Make currentProgress width = to upload progress percentage,
			    	    		//making currentProgress width a percentage of its parent's width
			    	    		currentProgress.style.width=Math.round((e.loaded/e.total)*100)+'%';
			    	    	}
			    	    };
                        xhr.onload = function(e){
			    	    	if(xhr.readyState == 4 && xhr.status == 200){
			    	    		var resp=JSON.parse(xhr.response);//Note: Response should be sanitized before attached to document
			    	    		currentProgress.style.width='100%';//Set current upload amount div width to 100% of parent
			    	    	    if(resp['src']){//File uploaded successfully if src was returned
			    	    	    	img.src=resp.src;//Set image tag src to uploaded image src
			    	    	    	dropBox.style.display='none';//Hide upload div
			    	    	        imgContainer.style.display='block';//Show image div
			    	    	        imgId=resp.id;//Update image id variable with new image id
			    	    	    }else{//Error
			    	    	    	alert(resp.error);
			    	    	    }
			    	    	    screeen.style.display='none';//Hide black screen
			    	    	    progress.style.display='none';//Hide progrees div
			    	    	    
			    	    	} 
                        };
			    	    xhr.send(formData);
    	           	}else{
			    		alert('Supported image types :' + supportedMemes.join(', '));
			    	}
			    }
			}
			<?php
			if($imgId){//Set image id in JavaScript if image already exist 
				echo "imgId=$imgId";
			}
			?>
			//-->
		</script>
	</body>
</html>
