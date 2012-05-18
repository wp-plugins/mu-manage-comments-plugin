<?PHP
/*
Plugin Name: MU Manage Comments Plugin
Plugin URI: http://www.BlogsEye.com/
Description: Lists unmoderated and spam comments on all MU blogs for moderation.
Version: 1.0
Author: Keith P. Graham
Author URI: http://www.BlogsEye.com/

This software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*/

function kpg_mu_manage_comments_control()  {
?>
<div class="wrap">
<h2>MU Manage Comments Plugin</h2>
<h3>Network Blogs</h3>
<?php
	$here=$_SERVER['REQUEST_URI'];
	if (strpos($here,'&')>0) {
		$here=substr($here,0,strpos($here,'&'));
	}
	$sel='';
	$where='';
	if (array_key_exists('a',$_GET)) {
		$sel=$_GET['a'];
	}
		    $cwhich="All comments";
	switch($sel) {
		case 'all':
		    $cwhich="All comments";
			$where="(comment_approved='0' or comment_approved='1' or comment_approved='spam')";
			break;
		case 'pending':
		    $cwhich="Pending comments";
			$where="comment_approved='0'";
			break;
		case 'spam':
		    $cwhich="Spam comments";
			$where="comment_approved='spam'";
			break;
		case 'spp':
		default:
		    $cwhich="Spam &amp; Pending comments";
			$where="(comment_approved='0' or comment_approved='spam')";
	}
?>
<p>
<a href="<?php echo $here; ?>&a=all">All comments</a>&nbsp;|&nbsp;
<a href="<?php echo $here; ?>&a=pending">Pending comments</a>&nbsp;|&nbsp;
<a href="<?php echo $here; ?>&a=spam">Spam comments</a>&nbsp;|&nbsp;
<a href="<?php echo $here; ?>&a=spp">Spam &amp; Pending comments</a>
</p>
<?php
	global $wpdb;
	$sql="SELECT blog_id FROM $wpdb->blogs WHERE  public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY blog_id";
	$blogs = $wpdb->get_results($sql, ARRAY_A );
	if (empty($blogs)) {
		echo "<h3>No Blogs Found</h3>";
		return;
	}
	$sql='';
	$pre=$wpdb->prefix;
	foreach ( (array) $blogs as $key=>$details ) {
		$blog=$details['blog_id'];
		$cblog_id=$blog;
		if ($blog=='1') {
			$blog='';
		} else {
			$blog=$blog.'_';
			$sql=$sql." UNION ";
		}
		$sql.="(select $cblog_id as blog_id,a.option_value as blogname, b.option_value as siteurl,comment_id,comment_date,comment_approved,comment_type,comment_author,comment_author_email,comment_author_IP,comment_content  from $pre".$blog."comments,$pre".$blog."options as a,$pre".$blog."options as b  where a.option_name='blogname' and b.option_name='siteurl' and $where)";
	}
	// we have a long and complicated sql string. We need to execute it.
	$comments = $wpdb->get_results($sql, ARRAY_A );
	$csite='';
	if (count($comments)==0) {
		echo "<h3>No comments found in $cwhich</h3>";
	} else {
?>
<table><tbody id="the-comment-list" class="list:comment">
<?php
	foreach ( (array) $comments as $key=>$details ) {
		$cblog_id=$details['blog_id'];
		$blogname=$details['blogname'];
		$siteurl=$details['siteurl'];
		$comment_id=$details['comment_id'];
		$comment_date=$details['comment_date'];
		$comment_date=date('Ymd',$comment_date);
		$comment_approved=$details['comment_approved'];
		$comment_type=$details['comment_type'];
		$comment_author=$details['comment_author'];
		$comment_author_email=$details['comment_author_email'];
		$comment_author_IP=$details['comment_author_IP'];
		$comment_content=$details['comment_content'];
		$comment_content=sanitize_text_field($comment_content);		
		if (strlen($comment_content)>64) $comment_content=substr($comment_content,0,61).'...';
		$blogadmin=esc_url(get_admin_url($blog));
		//siteurl + http or https
		if (force_ssl_admin()) {
			//$siteurl='https://'.$siteurl.'wp-admin/';
			$siteurl=$siteurl.'wp-admin/';
		} else {
			//$siteurl='http://'.$siteurl.'wp-admin/';
			$siteurl=$siteurl.'wp-admin/';
		}
		if ($csite!=$blogname) {
?>
	<tr id="comment-<?php echo $comment_id; ?>" class="comment">
	<td colspan="5">
	<a style="font-size:1.1em;font-weight:bold;" href="<?php echo $siteurl; ?>edit-comments.php" target="_blank"><?php echo $blogname; ?></a>
	</td>
	</tr>
<?php
		} 
		$csite=$blogname;
		$del_nonce = wp_create_nonce( "delete-comment_$comment_id" );
		$approve_nonce = wp_create_nonce( "approve-comment_$comment_id"  );
		
?>
<tr id="comment-<?php echo $comment_id; ?>" class="comment">
<td width="14px">&nbsp;</td>
<td class="author column-author">
<a href="mailto:<?php echo $comment_author_email; ?>"><?php echo $comment_author_email; ?></a><br>
<a href="<?php echo $siteurl; ?>edit-comments.php?s=<?php echo $comment_author_IP; ?>&mode=detail"><?php echo $comment_author_IP; ?></a>
</td>
<td class="comment column-comment">
<div class="submitted-on">Submitted on 
<?php echo $comment_date; ?>&nbsp;|&nbsp;
<?php
 	if ($comment_approved=='0') {
			echo "Unapproved";
	} else if ($comment_approved=='1') {
		echo "Approved";
	} else if (trim($comment_approved)=='spam') {
			echo "Marked as Spam";
	} else {
			echo "Status $comment_approved";
	}

?>
</div>
<?php echo $comment_content; ?>
<div class="row-actions">
<?php
if ($comment_approved=='0') {
?>
<span>
<a href="<?php echo $siteurl; ?>comment.php?c=<?php echo $comment_id; ?>&action=approvecomment&_wpnonce=<?php echo $approve_nonce; ?>" class="dim:the-comment-list:comment-<?php echo $comment_id; ?>" title="Approve this comment">Approve</a>
</span>
<span> | <a href="<?php echo $siteurl; ?>comment.php?c=<?php echo $comment_id; ?>&action=trashcomment&_wpnonce=<?php echo $del_nonce; ?>" class="delete:the-comment-list:comment-<?php echo $comment_id; ?>::trash=1 delete vim-d vim-destructive" title="Move this comment to the trash">Trash</a>
</span>
<?php
}
if ($comment_approved=='1') {
?>
<span>
<a href="<?php echo $siteurl; ?>comment.php?c=<?php echo $comment_id; ?>&action=unapprovecomment&_wpnonce=<?php echo $approve_nonce; ?>" class="dim:the-comment-list:comment-<?php echo $comment_id; ?>" title="Unapprove this comment">Unapprove</a>
</span>
<span> | <a href="<?php echo $siteurl; ?>comment.php?c=<?php echo $comment_id; ?>&action=trashcomment&_wpnonce=<?php echo $del_nonce; ?>" class="delete:the-comment-list:comment-<?php echo $comment_id; ?>::trash=1 delete vim-d vim-destructive" title="Move this comment to the trash">Trash</a>
</span>
<?php
}
if ($comment_approved!='spam') {
?>
<span> | <a href="<?php echo $siteurl; ?>comment.php?c=<?php echo $comment_id; ?>&action=spamcomment&_wpnonce=<?php echo $del_nonce; ?>" class="delete:the-comment-list:comment-<?php echo $comment_id; ?>::spam=1 vim-s vim-destructive" title="Mark this comment as spam">Mark as Spam</a>
</span>
<?php
} else {
?>
<span><a href="<?php echo $siteurl; ?>comment.php?c=<?php echo $comment_id; ?>&action=unspamcomment&_wpnonce=<?php echo $del_nonce; ?>" class="delete:the-comment-list:comment-<?php echo $comment_id; ?>::spam=1 vim-s vim-destructive" title="UnMark this comment as spam">Remove From Spam</a>
</span>
<span> | <a href="<?php echo $siteurl; ?>comment.php?c=<?php echo $comment_id; ?>&action=trashcomment&_wpnonce=<?php echo $del_nonce; ?>" class="delete:the-comment-list:comment-<?php echo $comment_id; ?>::trash=1 delete vim-d vim-destructive" title="Move this comment to the trash">Delete Permenantly</a>
</span>
<?php
}

// link to stop forum spam (if available) 
if (function_exists('kpg_sp_get_options')) {
	// new version of Stop Forum Spam is in place
	// get the sfs api flag
	$sfsconfig=kpg_sp_get_options();
	extract($sfsconfig);
	if (!empty($apikey)) {
		// need to calculate the ajaxurl for this comment
		$ajaxurl=get_admin_url($blog,'admin-ajax.php');
		
		$onclick="onclick=\"sfs_ajax_report_spam(this,'$comment_id','$cblog_id','$ajaxurl');return false;\"";

?>
<span> | <a href="#" <?php echo $onclick; ?> class="delete:the-comment-list:comment" title="Move this comment to the trash">Report to SFS</a>
</span>


<?php
	}

}
?>
</div>
</td>
</tr>

<?php
}  // end of foreach loop
?>
</tbody>
</table>
<?php
// advertising comes next	
} // end of if (count($comments))
?>
<p>&nbsp;</p>
<div style="position:relative;width:50%;background-color:ivory;border:#333333 medium groove;padding:4px;margin-left:4px;">
    <p>This plugin is free and I expect nothing in return. If you would like to support my programming, you can buy my book of short stories.</p>
    <p>Some plugin authors ask for a donation. I ask you to spend a very small amount for something that you will enjoy. eBook versions for the Kindle and other book readers start at 99&cent;. The book is much better than you might think, and it has some very good science fiction writers saying some very nice things. <br/>
      <a target="_blank" href="http://www.blogseye.com/buy-the-book/">Error Message Eyes: A Programmer's Guide to the Digital Soul</a></p>
    <p>A link on your blog to one of my personal sites would also be appreciated.</p>
    <p><a target="_blank" href="http://www.WestNyackHoney.com">West Nyack Honey</a> (I keep bees and sell the honey)<br />
      <a target="_blank" href="http://www.cthreepo.com/blog">Wandering Blog</a> (My personal Blog) <br />
      <a target="_blank" href="http://www.cthreepo.com">Resources for Science Fiction</a> (Writing Science Fiction) <br />
      <a target="_blank" href="http://www.jt30.com">The JT30 Page</a> (Amplified Blues Harmonica) <br />
      <a target="_blank" href="http://www.harpamps.com">Harp Amps</a> (Vacuum Tube Amplifiers for Blues) <br />
      <a target="_blank" href="http://www.blogseye.com">Blog&apos;s Eye</a> (PHP coding) <br />
      <a target="_blank" href="http://www.cthreepo.com/bees">Bee Progress Beekeeping Blog</a> (My adventures as a new beekeeper) </p>
  </div>
<script type="text/javascript" >
var sfs_ajax_who=null; //use this to update the message in the click
function sfs_ajax_report_spam(t,id,blog,url) {
	sfs_ajax_who=t;
	
	var data= {
		action: 'sfs_sub',
		blog_id: blog,
		comment_id: id
	}
	jQuery.get(url, data, sfs_ajax_return_spam);
}
function sfs_ajax_return_spam(response) {
    sfs_ajax_who.innerHTML="Spam reported";
	sfs_ajax_who.style.color="green";
	sfs_ajax_who.style.fontWeight="bolder";
	//alert(response);
	if (response.indexOf('data submitted successfully')>0) {
		return false;
	}
	if (response.indexOf('recent duplicate entry')>0) {
		sfs_ajax_who.innerHTML="Spam Already reported";
		sfs_ajax_who.style.color="brown";
		sfs_ajax_who.style.fontWeight="bolder";
		return false;
	}
	sfs_ajax_who.innerHTML="Error reporting spam";
	sfs_ajax_who.style.color="red";
	sfs_ajax_who.style.fontWeight="bolder";
	alert(response);
	return false;
}
</script>
		

</div>

<?php
}
function kpg_get_mca_blog_list($orderby='blog_id' ) {
	global $wpdb;
	$sql="SELECT blog_id FROM $wpdb->blogs WHERE  public = '1' AND archived = '0' AND mature = '0' AND spam = '0' AND deleted = '0' ORDER BY $orderby";
	
	$blogs = $wpdb->get_results($sql, ARRAY_A );
	if (empty($blogs)) {
		return array();
	}
	return $blogs;
}


// no unistall because I have not created any meta data to delete.
function kpg_mu_manage_comments_init() {
	if(current_user_can( 'manage_network' )) {
		add_options_page('MU Manage Comments', 'MU Manage Comments', 'manage_options','mu_manage_comments','kpg_mu_manage_comments_control');
	}
}
  // Plugin added to Wordpress plugin architecture
	add_action('admin_menu', 'kpg_mu_manage_comments_init');	

 	
?>