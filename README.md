SyRuP Parser
===========
0.0.3

A parser for SyRuP.
Converts valid SyRuP into a PHP array.

Demo App
-----------
There is a demo app in Demo/ that can be ran by installing on a PHP server, or alternately using Docker.

#Docker Image#
In this repo's root folder, run the following to create an image for the Demo app:

`docker build -t syrup-parser -f ./Dockerfile-demoapp .`

Then run this command create and run a container from that image:

`docker run --env=APACHE_DOCUMENT_ROOT=/var/www/html/Demo --workdir=/var/www/html -p 8181:80 -d syrup-parser:latest`
(In Docker Desktop you can press the run button next to the image. Map port 8181 or a port of your choosing to port 80)

You can then browse to http://localhost:8181/ to see the running demo.

SyRuP File
-----------

**S** y **R** u **P** = 
**S**tructured **R**eadable **P**arseable

SyRuP files are easy to read configuration files, very similar to .ini files.  
The primary difference between .syrp files and .ini files is that SyRuP files offer better support for lists and hierarchical information of infinite depth.

SyRuP is pronounced "sirrup"

SyRuP *files* are referred to as 
**SyRuP** files,
**Syrup** files, or
**.syrp** files

SyRuP *data* is referred to simply as 
**SyRuP** or **Syrup**

Downloads
-------------
+ [Syrup Command-line Parser (stable)](http://syrupfile.org/Downloads/syrup.phar)
+ [Syrup Command-line Parser (latest)](http://syrupfile.org/Downloads/syrup.latest.phar)
+ [Syrup 16 pixel icon (.ico)](http://syrupfile.org/Downloads/Icons/text-x-syrp.ico)
+ [Syrup 16 pixel icon (.gif)](http://syrupfile.org/Downloads/Icons/text-x-syrp-16.gif)
+ [Syrup 48 pixel icon (.gif)](http://syrupfile.org/Downloads/Icons/text-x-syrp-48.gif)

Command line parser notes
------------------
+ command-line parser is written in php and requires php to run
+ to run just type: php syrup.phar

Demonstration
-------------
http://syrupfile.org/

Background
-----------

SyRuP files were born as I struggled to pick a default file format for all of the application configuration files in an ActiveWAFL application.  

I liked the ease of .ini files.  They're human-readable and parsers for it are easy to find.
The problem with .ini's is when you want to represent slightly more complex data such as lists, arrays, matrices, and n-depth hierarchical relationships.

I then considered XML, YAML, NEON and JSON.  
All of them are powerful.  

YAML was the most readable.  
But writing it is fussy.  It requires exact spacing to make it work right.

JSON was the most writable.  
But it has curly brackets, and other ugly tokens, all over the place.

NEON supports setting inheritance.  
But it doesn't support deep levels of hierarchical data and inherited settings are not always easily human-readable.

XML offers the ability to tag information with other information.  
But it had ugly markup all over the place.

What I wanted was closest to YAML.  The ability to write hierarchical information in an easy-to-read format.  
However, I wanted something a bit less strict; Something that allowed the emphasis to be on readability and easy authoring.

Thus, SyRuP was born.

What a .syrp file looks like
-----------


>
	You probably would not mix and match all of these Elements.  This is here for full demonstration purposes.
	
    Heading1
        SubHeading1
            Value	Value	Value	Value
            Value	Value	Value	Value
            Value	Value	Value	Value

        SubHeading2
            Setting1
                Value
            Setting2
                Value

        SubHeading3
            Setting1	=	Value
            Setting2	=	Value
            Setting3	=	Value

            SubSubHeading1
                Setting1
                    Value	Value	Value
                Setting2
                    Value	Value	Value

Definitions
-----------

+	**Element**

	Any sequence of characters that is preceded by BOF|TAB and followed by EOL|EOF|TAB.

	*Example*
>	element1		element2	element3
	element4		element5

+    **Functional Indent**

	In Syrup a tab character (or two consecutive spaces where the second space's position in the line can be divided evenly by the tab-depth) is the delimiter between the Values in a Horizontal Value List (similar to CSV).
	However, consecutive tab characters, (or any space following consecutive spaces [ex: 3 spaces]) are
	treated as if they are a single tab character.

	This allows the Horizontal Value Lists to be lined up in a human readable row of cells.
	A Functional Indent is any number of tabs and/or consecutive spaces that exists between two consecutive elements.

	In the following example, there are two Functional Indents.  One following "element1" and one following "element2"
>	element1				element2						element3

+    **Depth**

	The number of Functional Indents encountered since the last EOL|BOF based on the parser's current cursor position.  
	(In the above example for Functional Indent, the Depth would be 2 if the cursor was at element3)

+    **Setting**

	Any Element that contains only a single Value directly beneath it or
	Any Element that contains a single Horizontal Value List directly beneath it or
	Any Element that contains a single Vertical Value List directly beneath it or
	Any Element that is not preceded by any other Elements on the same line and is followed by: (Equal Sign) FunctionalIndent Value  

    Note: Settings at equal depths and with the same parents (siblings) must have unique names because the names are used as unique keys

+    **Value**

	Any Element or list of Elements that has a parent Element and does not contain any child elements (example 1, example 2, example 3) or
	Any Element that is directly preceded by an equal sign and a Functional Indent (example 4)

	Note: Values are always part of a Key/Value pair where the Key is the Setting and the Value is the Value defined herein

	*Example 1*
>	SectionHeader 
		  Setting
			  Value

	*Example 2*
>	SectionHeader
		  Setting
			  Value	Value	Value

	*Example 3* 
>	SectionHeader
		  Setting
			  Value
			  Value
			  Value

	*Example 4* 
>	SectionHeader
		  Setting		=	Value

+    **Horizontal Value List**

	Multiple Values with the same parent Setting separated by \t or consecutive spaces (Example 2 above).
	Since it has no descendents, a Horizontal Value List is also a Value

+    **Vertical Value List**

	Multiple Values separated by lf, all of equal Depth, all with the same parent Setting (Example 3 above).
	Since it has no descendents, a Vertical Value List is also a Value

+    **Matrix**

	Multiple consecutive Horizontal Value Lists at the same Depth with the same parent Section Heading.

+    **Setting Assignment**

	A Setting and it's Value (and the intermediate equal sign, if using that method of assignment)

+    **Section Heading**

	Any Element that contains any Setting Assignments, at any Depth.

	A Section Heading can contain other Section Headings,  
	as long as there is a Setting Assignment somewhere in the descendent tree.

+    **Section**

	A Section Heading plus everything it contains