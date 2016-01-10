<?php

error_reporting(0);
include('simple_html_dom.php');
$html = new simple_html_dom();
	
function get_curl_data($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url); // target
	curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // provide a user-agent
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // follow any redirects
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // return the result
	$result = curl_exec($ch);
	curl_close($ch);
	return $result;
}


$root_site_url = "http://repertoire.apchq.com/entrepreneur-general/05/05/1";
//$root_site_url = "http://repertoire.apchq.com/entrepreneur-specialise/05/05/1";
//$root_site_url = "http://repertoire.apchq.com/entrepreneur-general/09/09/1";
//$root_site_url = "http://repertoire.apchq.com/entrepreneur-specialise/09/09/1";

$firstSection = explode("/",$root_site_url);
for($k=0;$k<count($firstSection)-1;$k++)
{
	$site_url_postion .= $firstSection[$k]."/";
}

$curl = get_curl_data($root_site_url);
$pagination_html = $html->load($curl);

$num = array();
foreach($pagination_html->find('div.pagination ul li a') as $row)
{
	$cell = $row->innertext;
    array_push($num, $cell);
}
$pagination_values = array_unique($num);


$category_array = array();
foreach($pagination_html->find('nav.main-menu ul li a#active span') as $row)
{
	$category = $row->innertext;
    array_push($category_array, $category);
}
$category_name = $category_array[0];

$filename = tempnam(sys_get_temp_dir(), "csv");
$file = fopen($filename,"w");
$fieldArray = array('Title','Category','Sub Category','Address','Phone1','Phone2','Email','Web','RBQ','Site URL');
fputcsv($file,$fieldArray);


$i=0;
if($pagination_values)
{
	foreach($pagination_values as $page_no)
	{
		$site_rul = $site_url_postion.$page_no;
		$curl = get_curl_data($site_rul);
		$div_result = $html->load($curl);
		
		foreach($div_result->find('div.result-item') as $row)
		{
			$title_text = $row->find('h5 a', 0);
			$title = $title_text->innertext;
			
			$sub_cat_list = $row->find('p.details strong');
			$sub_categories = '';
			foreach($row->find('p.details strong') as $sub_cat)
			{
				$sub_category = $sub_cat->innertext;
				$sub_categories .= $sub_category."/";
			}		
			
			$address_text = $row->find('p[2]', 0);
			foreach($address_text->find('strong') as $e)
				$e->outertext = '';	
			foreach($address_text->find('br') as $e)
				$e->outertext = '';	
			$address = $address_text->innertext;	
			
			
			$phone = $row->find('p[2] strong[2]', 0);
			foreach($phone->find('span') as $e)
				$e->outertext = '';		
			$phone_part = explode('&nbsp;&nbsp;&nbsp;',$phone->innertext);
			$phone1 = strip_tags($phone_part[0],'</span>');	
			$phone2 = strip_tags($phone_part[1],'</span>');					
			
			$web = $row->find('p.mail a[target]', 0);
			
			$rbq_text = $row->find('p.rbq a', 0);
			$rbq = explode("RBQ :",$rbq_text->innertext);
			
			$dataArray[$i]= array($title,$category_name,$sub_categories,$address,$phone1,$phone2,"",$web->href,$rbq[1],$site_rul);
			$i++;			
		}
		
		
	}
}
else
{
	$site_rul = $root_site_url;
	$curl = get_curl_data($site_rul);
	$div_result = $html->load($curl);
	
	foreach($div_result->find('div.result-item') as $row)
	{
		$title_text = $row->find('h5 a', 0);
		$title = $title_text->innertext;
		
		$sub_cat_list = $row->find('p.details strong');
		$sub_categories = '';
		foreach($row->find('p.details strong') as $sub_cat)
		{
			$sub_category = $sub_cat->innertext;
			$sub_categories .= $sub_category."/";
		}		
		
		$address_text = $row->find('p[2]', 0);
		foreach($address_text->find('strong') as $e)
			$e->outertext = '';	
		foreach($address_text->find('br') as $e)
			$e->outertext = '';	
		$address = $address_text->innertext;	
		
		
		$phone = $row->find('p[2] strong[2]', 0);
		foreach($phone->find('span') as $e)
			$e->outertext = '';		
		$phone_part = explode('&nbsp;&nbsp;&nbsp;',$phone->innertext);
		$phone1 = strip_tags($phone_part[0],'</span>');	
		$phone2 = strip_tags($phone_part[1],'</span>');					
		
		$web = $row->find('p.mail a[target]', 0);
		
		$rbq_text = $row->find('p.rbq a', 0);
		$rbq = explode("RBQ :",$rbq_text->innertext);
		
		$dataArray[$i]= array($title,$category_name,$sub_categories,$address,$phone1,$phone2,"",$web->href,$rbq[1],$site_rul);
		$i++;			
	}
}

foreach ($dataArray as $line) {
	fputcsv($file,$line);
}

fclose($file);
header("Content-Type: application/csv");
header("Content-Disposition: attachment;Filename=scraping.csv");
readfile($filename);
unlink($filename);



    
?>