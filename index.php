<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Mute POC | Livefyre</title>
		
		<link href="//netdna.bootstrapcdn.com/bootswatch/3.1.1/yeti/bootstrap.min.css" rel="stylesheet">
		
		<script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
		<script src="//cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js">?</script>		
		<script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

		<script src="http://zor.livefyre.com/wjs/v3.0/javascripts/livefyre.js" type="text/javascript"></script>
		<script src="http://client-solutions.ep.livefyre.com/media/Y2xpZW50LXNvbHV0aW9ucy5lcC5saXZlZnlyZS5jb20=/javascripts/customprofiles.js"></script>
	</head>
	<body>
		<div class="container-fluid">

			<div class="row">
				<div class="col-md-4">
					<h1>Mute POC</h1>
					<p>This page provides a POC of using a <a href="http://answers.livefyre.com/developers/reference/customization/adding-action-buttons/" target="_blank">Custom Action Button</a> to impement a mute feature in Livefyre comments.</p>
					<p>For the purposes of this example, the user's perferences are stored in a cookie for 7 days.</p>
					<button type="button" class="btn btn-warning btn-xs" onclick="Mute.purgeCookie()">Purge Cookie</button>

					<div class="btn-group">
						<button class="btn btn-info btn-xs dropdown-toggle" type="button" data-toggle="dropdown">
							Change Style <span class="caret"></span>
						</button>
						<ul class="dropdown-menu" role="menu">
							<li><a onclick="Mute.changeStyle('fade')">Fade muted posts</a></li>
							<li><a onclick="Mute.changeStyle('hide')">Hide muted posts</a></li>
						</ul>
					</div>
					<!--
					<div class="panel panel-default" style="margin-top:5%;">
  						<div class="panel-heading">Known Issues</div>
  						<div class="panel-body">
    						<ul>
								<li>Comments are re-drawn when a user changes between Newest/Oldest/Top Commented. Livefyre does not have JS events for this. Best option is to implement a DOM observer.</li>
								<li>Delay in commentCountUpdated firing.</li>
								<li>Performance concerns for large collections.</li>
								<li>For production, a customer's profile system would need to store/retrieve an array of muted authors per user.</li>
							</ul>
  						</div>
					</div>
					-->
				</div>
				
				<div class="col-md-7" style="border-left: 1px solid #eee; padding-top:2%;">
					<div id="livefyre"></div>
				</div>
			</div>

			
			<script type="text/javascript">
				/**
				 * Mute feature POC
				 * Author: pcolombo@livefyre.com
				**/
				var Mute = {};

					Mute.COOKIE_NAME = 'fyre-mute-authors';
					Mute.authors = [];
					Mute.style = 'fade';

					Mute.muteAuthor = function(authorId){
						if (this.authors.indexOf(authorId) == -1) {
							this.authors.push(authorId);
							$.cookie(this.COOKIE_NAME, this.authors)
							this.hide(authorId);
						} 
					};
					
					Mute.refresh = function(){
						//console.log('Mute.refresh');
						for(var i=0; i<this.authors.length; i++){
							this.hide(this.authors[i]);
						}
					};

					Mute.hide = function(authorId){
						var authorSelector = "article[data-author-id='"+authorId+"']";
						switch(this.style) {
							case 'hide':
								$(authorSelector).hide();
								break;	

							default:
							case 'fade':
								$(authorSelector).fadeTo('100',0.2);
								break;	
						}
					};

					Mute.init = function(){
						if ($.cookie(this.COOKIE_NAME) != undefined) {
							this.authors = $.cookie(this.COOKIE_NAME).split(",");
							console.log(this.authors);
							this.refresh();
						} else {
							$.cookie(this.COOKIE_NAME,this.authors,{expires: 7})
						}
					};

					Mute.changeStyle = function(newstyle){
						this.style = newstyle;
						this.refresh();
					}

					// Purge cookie for demo purposes only
					Mute.purgeCookie = function() {
						$.removeCookie(this.COOKIE_NAME);
						window.location.reload(true);
					};
				
				/**
				 * Define custom event handlers and action button
				**/
				
				// Define handlers for Livefyre JS Events
				var lfEventHandlers = {};
					
					// Init Mute once the widget has finshed rendering
					lfEventHandlers.initialRenderComplete = function(){
						
						// These don't really work since this handler is fired before the DOM refreshes with updated content 
						// Ideally replaced with a DOM observer
						$('.fyre-stream-sort-oldest').click(function(){ window.Mute.refresh(); });
						$('.fyre-stream-sort-newest').click(function(){ window.Mute.refresh(); });
						$('.fyre-stream-sort-top-comments').click(function(){ window.Mute.refresh(); });
						
						window.Mute.init();
					};
					
					// Hide muted authors when new comments are added to the stream
					lfEventHandlers.commentCountUpdated = function(data){
						window.Mute.refresh();
					};

				// Define custom action buttons
				var customActionButtons = [
					{
						key: "Mute this user",
						callback: function(contentInfo){
							window.Mute.muteAuthor(contentInfo.authorId);
						}
					}
				];

				/**
				 * Basic LF implementation
				**/

				var lfepAuthDelegate = new fyre.conv.SPAuthDelegate({engage: {app: "client-solutions.auth.fyre.co"}});

				var networkConfig = {
					network: 'client-solutions.fyre.co',
					authDelegate: lfepAuthDelegate
				}

				var convConfig = {
					siteId: '357316',
					articleId: 'basic_local_00011',
					el: 'livefyre',
					collectionMeta: 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJ0aXRsZSI6IkNvbW1lbnRzIiwidXJsIjoiaHR0cDpcL1wvbG9jYWwubGl2ZWZ5cmUuY29tXC9jb21tZW50cyIsImNoZWNrc3VtIjoiMWZhM2U1NGU5MDg0YzA4ZGNhNGQ5ZWMzNWY2YWU0OWIiLCJhcnRpY2xlSWQiOiJiYXNpY19sb2NhbF8wMDAxMSJ9.tjrmqJBuJFoBInQkTuh-_t6r5ohg5i1wKXKEGNmOeoE', 
					actionButtons: customActionButtons
				}

				var handlerConfig = function(widget) {
					widget.on('initialRenderComplete', window.lfEventHandlers.initialRenderComplete);
					widget.on('commentCountUpdated', window.lfEventHandlers.commentCountUpdated);
				};

				fyre.conv.load( networkConfig, [convConfig], handlerConfig);

			</script>



		</div> <!-- /.container -->
	</body>
</html>
