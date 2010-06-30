<?php
  header('Content-type: text/css');
  $background_color = urldecode($_GET["bgc"]);
?>

.tsr  {
	clear:both;
	height:auto;
	left:0;
	overflow:hidden;
	position:relative;
	top:0;
	width:100%;
	border:1 px solid #333333;
}

.tsr .text {
	clear:both;
	height:auto;
	left:0;
	overflow:hidden;
	padding:0;
	position:relative;
	top:0;
	width:100%;
}

.tsr .text .small .right .teaserText {
	height:95px;
	left:0;
	overflow:hidden;
	position:relative;
	top:0;
	width:70%;
}

.top5tsr  {
	clear:both;
	height:auto;
	left:0;
	overflow:visible;
	position:relative;
	top:0;
	width:100%;
}

.tsr .text .big .left .image .link .position {
	float:left;
	height:33px;
	padding:0;
	width:29px;
}

.tsr .text .big .right {
	background-color:#F3F4F4;
	border-bottom:1px solid #BEBEBE;
	border-left:1px solid #BEBEBE;
	border-right:1px solid #BEBEBE;
	float:left;
	height:auto;
	left:0;
	overflow:hidden;
	position:relative;
	top:0;
	width:100%;
}

.breadcrumb  {
	display:none;
	height:auto;
	left:0;
	position:relative;
	top:0;
	width:auto;
}

.tsr .text .big .right .image .link .top5tsrBild {
	float:left;
	height:auto;
	left:2px;
	position:relative;
	top:2px;
	width:auto;
}

.tsr .text .big .right .teaserText {
	margin-bottom:5px;
	left:0;
	overflow:hidden;
	position:relative;
	top:0;
}

.tsr .text .big .right .teaserText .headline {
	display:block;
	left:5px;
	position:relative;
	top:0px;
	width:auto;
}
.font_46 {
	color:#000000;
	font-weight:900;
	font:400 10px Arial,Verdana,sans-serif;
	text-decoration:none;
	text-transform:uppercase;
}
.tsr .text .top5tsr {
	clear:both;
	height:auto;
	left:0;
	overflow:visible;
	position:relative;
	top:0;
	width:100%;
}
.tsr .text .small .left {
	background-color:#F3F4F4;
	border-bottom:1px solid #BEBEBE;
	border-left:1px solid #BEBEBE;
	float:left;
	height:55px;
	left:0;
	overflow:hidden;
	padding:0;
	position:relative;
	top:0;
	width:29px;
}
body {
	color:#141414;
	font:400 11px Arial,Verdana,sans-serif;
	text-decoration:none;
}
img {
	border:medium none;
}
.tsr .text .small .right {
	background-color:#F3F4F4;
	border-bottom:1px solid #BEBEBE;
	border-left:1px solid #BEBEBE;
	border-right:1px solid #BEBEBE;
	float:left;
	height:55px;
	left:0;
	overflow:hidden;
	position:relative;
	top:0;
	width:100%;
}
.tsr .text .small .right .teaserText {
	height:95px;
	left:0;
	overflow:hidden;
	position:relative;
	top:0;
	width:70%;
}
.tsr .text .small .right .teaserText .headline {
	display:block;
	left:5px;
	position:relative;
	top:5px;
	width:auto;
}

.tsr .text .top5tsr .right .texte {
	text-align:left;
}

.tsr .text .small .right .teaserText {
	position: relative;
	top: 0px;
	left: 0px;
	width: 70%;
	height: 80px;
	overflow: hidden;
}

.tsr .text .small .right .teaserText .headline {
	position: relative;
	top: 5px;
	left: 5px;
	width: auto;
	height: 12px;
}

.tsr .text .small .right .teaserText .headline {
	height:12px;
	left:5px;
	position:relative;
	top:5px;
	width:auto;
}
.font_43 {
	color:#000000;
	font:800 10px Arial,Verdana,sans-serif;
	text-decoration:none;
}
.tsr .text .top5tsr .right .teaserText .headline h3 {
	margin:0;
	text-align:left;
}

.tsr .headline {
	height:auto;
	left:0;
	overflow:hidden;
	position:relative;
	top:0;
	width:100%;
}

.tsr .text .small .right {
	background-color:#FCFCFC;
	border-bottom:1px solid #BEBEBE;
	border-left:1px solid #BEBEBE;
	border-right:1px solid #BEBEBE;
	float:left;
	left:0;
	overflow:hidden;
	position:relative;
	top:0;
	width:100%;
}

.tsr .text .small .left {
	background-color:#FCFCFC;
	border-bottom:1px solid #BEBEBE;
	border-left:1px solid #BEBEBE;
	float:left;
	height:33px;
	left:0;
	overflow:hidden;
	padding:0;
	position:relative;
	top:0;
	width:29px;
}

.tsr .text .small {
	padding:0;
}

.tsr .text .small .right .image .link .top5tsrBild {
	display:none;
}

.tsr .text .small .right img {
	display:none;
}

.event-item-link{
	color: black;
	text-decoration: none;
}

.event-item-link:hover{
	color: black;
	text-decoration: none;
}

.events-container{
	clear:both;
	float:left;
	width:100%;
	padding: 5px;
	background-color: <?php echo $background_color;?>;
}

.event-item{
	clear:both;
	background-color: #E9F1E3;
	border: 1px solid #B7B8B8;
	width:100%;
	margin-bottom: 3px;
	margin-top: 3px;
	overflow: hidden;
}

.event-item:hover{
	background-color: #FFD83D;
}

.event-item-square{
	width:60px;
	height:60px;
	margin: 5px;
}

.event-item-content{
	margin: 5px;
}

.event-item-content-name{
	margin: 0px 5px;
	font-size: large;
	color: #3B434A;
}

.event-item-content-date{
	margin: 0px 5px;
}

.event-item-content-city{
	margin: 0px 5px;
}

#eventoni_umkreissuche, #eventoni_pagination{
	color:#ffffff;
	font-size:10px;
	padding:5px;
}

#eventoni_container{
	background-color: <?php echo $background_color;?>;
	margin-top:10px;
	position:relative;
	border:1px solid #dddddd;
	text-align:center;
	overflow:auto;
	font-face:serif;
	font-size:14px;
	color:#000000;
	font-weight:600;
}

#eventoni_status{
	color:#ffffff;
}

#top5_1, #top5_2, #top5_3, #top5_4, #top5_5, #eventoni_container {
	display:none;
}