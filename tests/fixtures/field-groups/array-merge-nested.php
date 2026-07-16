<?php
acf_add_local_field_group( array_merge(
	array( "key" => "group_nested" ),
	array_merge(
		array( "title" => "Nested" ),
		array( "fields" => array() )
	)
) );

