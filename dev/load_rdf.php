<!-- 
Copyright ?2014 The Regents of the University of Michigan
 
Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at
 
http://www.apache.org/licenses/LICENSE-2.0
 
Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 
For more information, questions, or permission requests, please contact:
Yongqun "Oliver" He - yongqunh@med.umich.edu
Unit for Laboratory Animal Medicine, Center for Computational Medicine & Bioinformatics
University of Michigan, Ann Arbor, MI 48109, USA
He Group:  http://www.hegroup.org
-->
<!--
Author: Zuoshuang Xiang, Yongqun He
The University Of Michigan
He Group
Date: June 2008 - November 2014
Purpose:This is the PHP code that loads an ontology to a Virtuoso RDF triple store that is used by Ontobee. 
-->

<?php
chdir(dirname(__FILE__));
ini_set("memory_limit", "8192M");
set_time_limit(60*60);

include_once('functions.php');


$db = ADONewConnection($driver);
$db->Connect($host, $username, $password, $database);
$strSql = "SELECT * FROM ontology where end_point='http://sparql.hegroup.org/sparql'";


// if ontology specified
if (sizeof($argv)==2) {
	$strSql .= " and ontology_abbrv = '{$argv[1]}'";
}

print("Loading RDF store:\n");

//echo $strSql;
$tmp_dir='/tmp/rdf';

if(file_exists("$tmp_dir/load_rdf.log")) unlink("$tmp_dir/load_rdf.log");
if (!file_exists($tmp_dir)) mkdir($tmp_dir);

$rs = $db->Execute($strSql);

$array_name_space=array();
foreach($rs as $row) {
	$id=$row['id'];
	$graph_url=$row['ontology_graph_url'];
//download owl files
	$reload=true;
	$file_name="$tmp_dir/$id.owl";
	$graph_url=$row['ontology_graph_url'];
	if(file_exists($file_name)) unlink($file_name);
	if(file_exists("$tmp_dir/mapping_$file_name")) unlink("$tmp_dir/mapping_$file_name");

	if ($id=='ncbi_taxonomy') {
		$download_url = $row['ontology_url'];
		print("$id: loading data from $download_url\n");
		system("wget -q $download_url -O $file_name");

		if (file_exists($file_name)) print("$id: loaded from $download_url\n");
		
	}else {
		if ($row['download']!='') {
			if ($row['do_merge']=='y') {
				mergeOwlFiles($row['download'], $tmp_dir, $id);
			}
			else {
				if (strpos($row['download'], '.zip')!==false) {
					system("wget ".$row['download']." -O $tmp_dir/$id.zip");
					system("unzip $tmp_dir/$id.zip -d /tmp/");
					
					system('unzip -l '.$tmp_dir.'/'.$id.'.zip |grep .owl |awk \'{cmd="mv /tmp/" $4 " '.$file_name.'"; system(cmd); close(cmd);}\'');
				}
				else {
					print("$id: loading data from ".$row['download']."\n");
					system("wget -q ".$row['download']." -O $file_name");
				}
			}
		}
		
		if ($row['source']!='' && !file_exists($file_name)) {
			mergeOwlFiles($row['source'], $tmp_dir, $id);
		}
		
		if (!file_exists($file_name)) {
			mergeOwlFiles($row['ontology_url'], $tmp_dir, $id);
		}
	}

	if (!file_exists($file_name)) {
		print("Failed to download owl file!\n");
		$reload=false;
	}
	else {
		$md5 =  md5_file($file_name);
		if ($md5==$row['md5'] && $row['loaded']=='y') $reload=false;
	}
	
	
	if ($reload) {
		$strSql = "UPDATE ontology SET loaded='n' where id = '$id'";
		$db->Execute($strSql);

		exec('/data/usr/local/virtuoso/bin/isql 1111 dba dJay0D2a verbose=on banner=off prompt=off echo=ON errors=stdout exec="log_enable(3,1); sparql  clear graph <'.$graph_url.'>; DB.DBA.RDF_LOAD_RDFXML_MT (file_to_string_output (\''.$file_name.'\'), \'\', \''.$graph_url.'\');"', $output);
		
		$strOutput=join("\n", $output);
		unset($output);

		$strSql = "UPDATE ontofox.ontology SET log=".$db->qstr($strOutput)." where id = '$id'";
		$db->Execute($strSql);


		if (strpos($strOutput, 'Error')===false) {
			$strSql = "UPDATE ontology SET loaded='y', md5='$md5', last_update=now() where id = '$id'";
			$db->Execute($strSql);
			print("$id loaded\n");
		}
		else {
			print("$id failed\n");
		}



		if ($id=='vaccine') {
			$reasoned_file_name = "$tmp_dir/$id"."_reason.owl"; 
			if(file_exists($reasoned_file_name)) unlink($reasoned_file_name);
			system("java -Xmx8g -cp .:./libs/* org.hegroup.rdfstore.OWLReason $file_name $reasoned_file_name");
			if (file_exists($reasoned_file_name)) {
				$reasoned_graph_url=str_replace('/merged/', '/inferred/', $graph_url);
				
				exec('/data/usr/local/virtuoso/bin/isql 1111 dba dJay0D2a verbose=on banner=off prompt=off echo=ON errors=stdout exec="log_enable(3,1); sparql clear graph <'.$reasoned_graph_url.'>; DB.DBA.RDF_LOAD_RDFXML_MT (file_to_string_output (\''.$reasoned_file_name.'\'), \'\', \''.$reasoned_graph_url.'\');"', $output);
			}
			
		}

	}
}

function mergeOwlFiles($download_url, $tmp_dir, $id){
	$file_name="$tmp_dir/$id.owl";
	$tokens=explode('|', $download_url);
	if (sizeof($tokens)==2) $download_url=$tokens[1];
	else  $download_url=$tokens[0];

	if (strpos($download_url, '.zip')!==false) {
		system("wget $download_url -O $tmp_dir/$id.zip");
		system("unzip $tmp_dir/$id.zip -d /tmp/");
		
		system('unzip -l '.$tmp_dir.'/'.$id.'.zip |grep .owl |awk \'{cmd="mv /tmp/" $4 " '.$tmp_dir.'/'.$id.'.from_zip.owl"; system(cmd); close(cmd);}\'');
		$download_url="file://$tmp_dir/$id.from_zip.owl";
	}

	if (strpos($download_url, '.owl')!==false || $download_url=='http://www.ifomis.org/bfo/1.1') {
		print("$id: loading data from $download_url\n");
		print("$id: getting final url $download_url\n");
		$download_url=get_final_url($download_url);
		$json_settings = array();
		$json_settings['download_url'] = $download_url;
		$json_settings['output_file'] = $file_name;
		
		$json_settings['mapping'] = getMapping($download_url, "$tmp_dir/$id.mapping");
		
		file_put_contents("$tmp_dir/$id.json", json_encode($json_settings));
		
		system("java -Xmx8g -cp .:./libs/* org.hegroup.rdfstore.OWLMerge $tmp_dir/$id.json");
	}
	if (file_exists($file_name)) print("$file_name downloaded from $download_url\n");
}

function getMapping($download_url, $output_file) {
	$a_mapping=array();
	$pos=strrpos($download_url, '/');
	if ($pos!==false) {
		$strFolder=substr($download_url, 0, $pos);
		system("wget -q $strFolder/catalog-v001.xml -O $output_file");
		$str_mapping=file_get_contents($output_file);
		
		preg_match_all('/<uri id="[^"]+" name="([^"]+)" uri="([^"]+)"\/>/', $str_mapping, $matches, PREG_SET_ORDER);
		foreach($matches as $match) {
			$mapping['to']=$match[1];
			$from=$strFolder.'/'.$match[2];
			
			while (preg_match('/\/[^\/\.]+\/\.\./', $from)) {
				$from=preg_replace('/\/[^\/\.]+\/\.\./', '', $from);
			}
			$mapping['from']=$from;
			$a_mapping[]=$mapping;
		}
	}
	
//	print_r($a_mapping);
	return($a_mapping);
}
?>
Done!
