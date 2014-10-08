#!/usr/bin/perl

use strict;
use warnings;

use XML::LibXML;
use DBI;

my $filename = "../project/config.xml";

my $parser = XML::LibXML->new();
my $dom = $parser->load_xml(location => $filename);

my $root = $dom->getDocumentElement();

my %elements = ();
my @db_array = ();

process_node($root);

my $db = connect_to_db(@db_array);

foreach my $tag (sort {lc $a cmp lc $b } keys %elements) {
    my $multiple = $elements{$tag}{'AllowMultiple'} == 1 ? 0 : 1;
    if ($elements{$tag}{'Name'} eq 'DoubleDataEntryInstruments') {
        $multiple = 1;
    }
    my $query = "INSERT INTO ConfigSettings 
                 (`Name`, `Description`, `Visible`, `AllowMultiple`)
                 VALUES (
                     ?, 
                     ?,
                     TRUE,
                     ?
                 )";
    my $sth = $db->prepare($query);
    $sth->execute("$elements{$tag}{'Name'}", "$elements{$tag}{'Description'}", $multiple);
}
 
# update parents when all nodes loaded
foreach my $tag (sort {lc $a cmp lc $b } keys %elements) {
    if ($elements{$tag}{'Parent'}) {
        my $query1 = "SELECT ID FROM ConfigSettings WHERE Name='$elements{$tag}{'Parent'}'";
        my $sth1 = $db->prepare($query1);
        $sth1->execute();
        # fix if names are not unique 
        my $parentID = $sth1->fetchrow_array();
        my $query = "UPDATE ConfigSettings SET Parent = ? WHERE Name = ?"; 
                 
        my $sth = $db->prepare($query);
        $sth->execute($parentID, $elements{$tag}{'Name'});
    }
}

#insert values, bad implementation since check parents which can be NULL 
#(due to the uniqueness of Names which may not always be unique (PSCID and ExternalID children)) 
foreach my $tag (sort {lc $a cmp lc $b } keys %elements) {
    foreach my $value (@{$elements{$tag}{'Value'}}) {
        my $query_values = "INSERT INTO Config 
                  (`ConfigID`, `Value`) 
                  VALUES (
                      (SELECT ID FROM ConfigSettings WHERE Name=? AND (Parent=(SELECT ID FROM ConfigSettings WHERE Name=?) OR Parent is NULL)),
                      ?
                  )";
        my $sth_values = $db->prepare($query_values);
        $sth_values->execute($elements{$tag}{'Name'}, $elements{$tag}{'Parent'}, $value);
    }
}

$db->disconnect;

=debug
foreach my $tag (sort {lc $a cmp lc $b } keys %elements) {
    print "$tag:\n";
    foreach my $item (keys %{$elements{$tag}}) {
        if ($item ne "Value") { 
            print "$item = $elements{$tag}{$item}\n";
	} else {
            print "$item = ";
            foreach my $value (@{$elements{$tag}{$item}}) {
                print "$value \t";
	    }
            print "\n";
	}
    }
    print "\n";
}
=cut

sub process_node {
    my $node = shift;
    for my $child ($node->childNodes) {
        if ($child->nodeName ne 'main_menu_tabs'
         && $child->nodeName ne 'database'
         && $child->nodeName ne 'Projects'
         && $child->nodeName ne 'subprojects'
         && $child->nodeName ne 'visitLabel'
         && $child->nodeName ne 'Studylinks'
         && $child->nodeName ne 'links'
         && $child->nodeName ne 'excluded_instruments'
         && $child->nodeName ne 'Certification'
         && $child->nodeName ne 'ConsentModule'
         && $child->nodeName ne 'structure'
         && $child->nodeName ne 'PSCID'
         && $child->nodeName ne 'ExternalID' 
         && ! $child->hasAttributes()) {
            if ($child->nodeType == 1) {
                my $name = $child->nodeName;
                my $parent = $node->nodeName;
                my $key = join "+", $parent, $name;
                $elements{$key}{'Name'} = $name;
                $elements{$key}{'Description'} = $name;
                $elements{$key}{'Parent'} = $parent;
                $elements{$key}{'Visible'} = 1;
                if (! exists $elements{$key}{'AllowMultiple'}) {
                   $elements{$key}{'AllowMultiple'} = 1;
		}
		else {
                    $elements{$key}{'AllowMultiple'} += 1;
                }
                
                if ($child->firstChild && $child->firstChild->nodeType == 3) { 
                    my $value = $child->firstChild->nodeValue;
                    # skip empty
                    if (ord($value) != 10) {
                        # for multiple
                        if (! exists $elements{$key}{'Value'}) {
                            my @array = ($value);
                            $elements{$key}{'Value'} = \@array;
                            # set type
                        } else {
                            push($elements{$key}{'Value'}, $value);
			}
      
                    }
                }  
                process_node($child);
            }
        } elsif ($child->nodeName eq "database") {
             my $db_host;
             my $db_name;
             my $db_user;
             my $db_pass; 
             foreach my $kid ($child->getChildnodes) {
                 if ($kid->nodeName eq "host") {
                     $db_host = $kid->firstChild->nodeValue;
                 } elsif ($kid->nodeName eq "username") {
                     $db_user = $kid->firstChild->nodeValue;
		 } elsif ($kid->nodeName eq "password") {
                     $db_pass = $kid->firstChild->nodeValue;
		 } elsif ($kid->nodeName eq "database") {
                     $db_name = $kid->firstChild->nodeValue;
		 }
             }
             push(@db_array, ($db_name, $db_user, $db_pass, $db_host));         
	}
    }
}


sub connect_to_db {
    my ($db_name, $db_user, $db_pass, $db_host) = @_;
    my $db_dsn = "DBI:mysql:database=$db_name;host=$db_host";
    my $dbh = DBI->connect($db_dsn, $db_user, $db_pass) 
        or die "DB connection failed\nDBI Error: ". $DBI::errstr . "\n";
    return $dbh;
    
}
