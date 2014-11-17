/*
Copyright ?2014 The Regents of the University of Michigan
 
Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at
 
http://www.apache.org/licenses/LICENSE-2.0
 
Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the License for the specific language governing permissions and limitations under the License.
 
For more information, questions, or permission requests, please contact:
Yongqun 揙liver?He - yongqunh@med.umich.edu
Unit for Laboratory Animal Medicine, Center for Computational Medicine & Bioinformatics
University of Michigan, Ann Arbor, MI 48109, USA
He Group:  http://www.hegroup.org
*/

/*
Author: Zuoshuang Xiang, Yongqun He
The University Of Michigan
He Group
Date: June 2008 - November 2014
Purpose: The MySQL script for creating the OntoFox database 'ontology' table.  
Note: As seen in the Ontobee PHP file /inc/Classes.php, Ontobee actually queries this OntoFox ontology table for reading the ontology information. --Oliver 
*/

CREATE TABLE `ontology` (
  `id` varchar(45) NOT NULL,
  `ontology_abbrv` varchar(45) NOT NULL,
  `ontology_url` varchar(128) NOT NULL,
  `ontology_fullname` varchar(256) NOT NULL,
  `end_point` varchar(128) NOT NULL,
  `ontology_graph_url` varchar(128) NOT NULL,
  `to_list` varchar(1) NOT NULL DEFAULT 'y',
  `foundry` varchar(3) DEFAULT NULL,
  `download` varchar(256) DEFAULT NULL,
  `source` varchar(256) DEFAULT NULL,
  `format` varchar(45) DEFAULT NULL,
  `alternative_download` varchar(256) DEFAULT NULL,
  `prerelease_download` varchar(256) DEFAULT NULL,
  `basic_download` varchar(256) DEFAULT NULL,
  `home` varchar(256) DEFAULT NULL,
  `documentation` varchar(256) DEFAULT NULL,
  `contact` varchar(256) DEFAULT NULL,
  `help` varchar(128) DEFAULT NULL,
  `description` varchar(2048) DEFAULT NULL,
  `relevant_organism` varchar(45) DEFAULT NULL,
  `loaded` varchar(1) NOT NULL DEFAULT 'n',
  `md5` varchar(32) DEFAULT NULL,
  `url_eg` varchar(128) DEFAULT NULL,
  `log` varchar(1024) DEFAULT NULL,
  `last_update` datetime DEFAULT NULL,
  `do_merge` varchar(1) NOT NULL DEFAULT 'y',
  PRIMARY KEY (`id`) USING BTREE,
  KEY `index_abbrv` (`ontology_abbrv`),
  KEY `index_fullname` (`ontology_fullname`),
  KEY `index_url` (`ontology_url`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=latin1$$


