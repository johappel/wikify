jQuery(document).ready(function($) {
	


    tinymce.create('tinymce.plugins.wikify_plugin', {
		maxId:0,
		wikiwords:{},
		activeNode:null,		
		
		getWikiWords: function(el){
		
			
		
			var ids = [], ww;
			
			ww = el.getElementsByClassName('linkword');
			
			
			for(i=0;i<ww.length;i++){
				var splitted = ww[i].innerHTML.split('|');
				var newword = splitted[0].trim();
				if(!this.wikiwords[ww[i].id]){
					this.wikiwords[ww[i].id] = {};
				}
				this.wikiwords[ww[i].id].label=newword;
				ids.push(newword);
				
				if(this.wikiwords[ww[i].id].checked){
					
					if(this.wikiwords[ww[i].id].exists){
						ww[i].className = 'wikify linkword existing-wikipost';
					}else{
						ww[i].className = 'wikify linkword';
					}
				}
				if(this.wikiwords[ww[i].id].slug){
					ww[i].href = this.wikiwords[ww[i].id].slug;
				}
			}
			if(ids.length){
				if	(	(!this.wikiwords.all) || 
						(ids!==this.wikiwords.all)
					){
						this.wikiwords.all =  ids;
						//console.info('checkserver'); 
						
						
						
						this.checkWikiWords();
						
						
				}
			}
						
			
		},
		setWikiWordStyles: function(){
			var c = tinyMCE.activeEditor.getContent();
			var i=0;
			c = c.replace(/\[\[([^\]]+)\]\]/g, function(match, contents){
				i++;
				contents = contents.replace(/<[^>]*>/g,'');
				contents = contents.replace(/[\[\]]/g,'').trim();
				var parts = contents.split('|');
				if(parts.length==1){
					bracket = "[[<a href=\"#\" id=\"wikiword-"+i+"\" class=\"wikify linkword\">"+parts[0].trim()+"</a>]]";
				}else{
					bracket = "[[<a href=\"#\" id=\"wikiword-"+i+"\" class=\"wikify linkword\">"+parts[0].trim()+"</a>|<span class=\"wikify linklabel\">"+ parts[1].trim() +"</span>]]";
				}
				return bracket;
			});
			this.maxId = i;
			tinyMCE.activeEditor.setContent(c);
			
			
			
		},
		checkWikiWords: function(){
			
			var p=this, abord = true;
			
			for(elem in p.wikiwords){
				if(elem == 'all') continue;
				if(!p.wikiwords[elem].checked) {
					abord = false;
					break;
				}
			}
			if(abord) return;
			
			$.ajax({
				url: ajaxurl,
				data: {
					'action':'wikify_post_exists',
					'contents' : p.wikiwords.all.join()
				},
				success:function(data) {
					data = jQuery.parseJSON(data);
					if(data.result){
						//reset
						for(elem in p.wikiwords){
							if(elem == 'all') continue;
							p.wikiwords[elem].exists = false;
						}
						//compare with serverdata
						for(i=0;i<data.result.length;i++){
							var obj = data.result[i];
							
							
							for(elem in p.wikiwords){
								
								if(elem == 'all') continue;
								
								if(obj.label == p.wikiwords[elem].label){
									p.wikiwords[elem].checked = true;
									if(obj.slug){
										//console.log(obj.slug)
										p.wikiwords[elem].exists = true;
										p.wikiwords[elem].slug = obj.slug;
									}else{
										p.wikiwords[elem].slug = '/wp-admin/post-new.php?post_title='+obj.label;
									}
									
								}
								
								
							}
						}
						//console.log(p.wikiwords);
						//p.getWikiWords(p.ed.dom.doc);
					}
					
				},
				error: function(errorThrown){
					//console.log(errorThrown);
				}
			});
		},
		
		init : function(ed, url) {
		
			this.ed = ed;
			
			var p = this;
			
						
			ed.on('change',function(e){
				//console.log('changed');
				p.getWikiWords(e.target.dom.doc);
				
			});
			//removes wikiword styles
			tinyMCE.activeEditor.on('GetContent',function(e){
				e.content = e.content.replace(/(\[\[[^\]]+\]\])/g, function(match, contents){
					return 	contents.replace(/<[^>]*>/g,'');
				});
			});
			
			//mask wikiwords onLoad
			ed.on('LoadContent',function(e){
				p.setWikiWordStyles();
				p.wikiwords={},
				p.getWikiWords(ed.dom.doc);
				//console.log('content loaded');
			});
			
			
			// Register command for when button is clicked
			ed.addCommand('wikify_insert_brackets', function() {
				
				if($(tinyMCE.activeEditor.selection.getNode()).hasClass('wikify')) return;
				
				selected = tinyMCE.activeEditor.selection.getContent();
				
				
				if( selected ){
					p.maxId ++;
					//If text is selected when button is clicked
					//Wrap brackets around it.
					content =  '[[<a href="#" id="wikiword-'+p.maxId+'" class="wikify linkword">'+selected.trim()+'</a>]]';
					if(selected.substring(selected.length-1) == ' ') content += ' ';
					//console.log('selected brackets');
					p.getWikiWords(ed.dom.doc);
					
					
					
				}else{
					
					content =  '';
										
					
				}

				tinymce.execCommand('mceInsertContent', false, content);
				
					
			});
			
			// Register command for when button is clicked
			ed.addCommand('wikify_insert_brackets_label', function() {
			
				if($(tinyMCE.activeEditor.selection.getNode()).hasClass('wikify')) return;
				
				selected = tinyMCE.activeEditor.selection.getContent();

				if( selected ){
					p.maxId ++;
					//If text is selected when button is clicked
					//Wrap brackets around it.
					
					var wikiword = prompt(tinyMCE.activeEditor.getParam('wikify_lang_prompt'));
					if(wikiword == null){
					
						return;
					
					}else{
						
						content =  '[[<a href="/'+wikiword+'" id="wikiword-'+p.maxId+'"  class="wikify linkword">'+wikiword+'</a>|<span class="wikify linklabel">'+selected.trim()+'</span>]]';
						if(selected.substring(selected.length-1) == ' ') content += ' ';
						//console.log('wikify_insert_brackets_label');
						p.getWikiWords(ed.dom.doc);
					
					}
					
				}else{
					
					content =  '';
										
					
				}

				tinymce.execCommand('mceInsertContent', false, content);
				
					
			});
			
            // Register buttons - trigger above command when clicked
            ed.addButton('wikify_button', {title : 'Wikify', cmd : 'wikify_insert_brackets', image: url + '/icons/wikify.png' });
			ed.addButton('wikify_split_button', {title : 'Wikify Label', cmd : 'wikify_insert_brackets_label', image: url + '/icons/wikilabel.png' });
			ed.on('init', function() {
				ed.execCommand('wikify_content', false, null, {skip_focus: true});
			});
			
		}});
           
    

    // Register our TinyMCE plugin
    // first parameter is the button ID
    // second parameter must match the first parameter of the tinymce.create() function above
    tinymce.PluginManager.add('wikify_button', tinymce.plugins.wikify_plugin);
});
