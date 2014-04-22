SyRuP Parser
===========

A parser for SyRuP.
Converts valid SyRuP into a PHP array.

Note:
about how consecutive spaces work
escaping
quotes and block quotes
would like ot be able to tab alogn block qquotes and have it parse as a steady string
found a bug when doing a vertical list and having a horizontal list as one of its members
sequences of {${ and }$} and the contents therin will be removed

SyRuP File
-----------

**S** y **R** u **P** = 
**S**tructured **R**eadable **P**arseable

SyRuP files are easy to read configuration files, very similar to .ini files.
The primary difference between .syrp files and .ini files is that SyRuP files offer better support for lists and hierarchical information.

SyRuP is pronounced "sirrup"

SyRuP *files* are referred to as 
**SyRuP** files,
**Syrup** files, or
**.syrp** files

SyRuP *data* is referred to simply as 
**SyRuP** or **Syrup**


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
	
	SectionHeading

		SectionHeading
				Value	Value	Value	Value
				Value	Value	Value	Value
				Value	Value	Value	Value

		SectionHeading
				Setting
						Value
				Setting
						Value

		SectionHeading
			Setting		=		Value
			Setting		=		Value
			Setting		=		Value

			SectionHeading
				Setting
					Value		Value		Value
				Setting
					Value		Value		Value


Definitions
-----------

+	**Element**

	Any sequence of characters that is preceded by BOF|TAB and followed by EOL|EOF|TAB.

	*Example*
>	element1		element2	element3
	element4		element5

+    **Functional Indent**

	In Syrup a tab character (or two consecutive spaces) is the delimiter between the Values in a Horizontal Value List (similar to CSV).
	However, consecutive tab characters, (or consecutive consecutive spaces [ex: 4 spaces]) are 
	treated as if they are a single tab character.
	This allows the Horizontal Value Lists to be lined up in a human readable row of cells.

	A Functional Indent is any number of tabs and/or consecutive spaces that exists between two consecutive elements.

	In the following example, there are two Functional Indents.  One following "element1" and one following "element2"
>	element1				element2						element3

+    **Depth**

	The number of Functional Indents encountered since the last eol|bof based on the parser's current cursor position.
	(In the above example for Functional Indent, the Depth would be 2 if the cursor was at element3)

+    **Setting**

	Any Element that contains only a single Value directly beneath it or
	Any Element that contains a single Horizontal Value List directly beneath it or
	Any Element that contains a single Vertical Value List directly beneath it or
	Any Element that is not preceded by any other Elements on the same line and is followed by: (Equal Sign) FunctionalIndent Value

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
