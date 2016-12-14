<?php

/**
 * A class containing a diff implementation.
 *
 * Modified version of class.Diff.php
 * by Stephen Morley - http://stephenmorley.org/ - and
 * released under the terms of the CC0 1.0 Universal legal code:
 * http://creativecommons.org/publicdomain/zero/1.0/legalcode
 */

/**
 * A class containing functions for
 * computing diffs and formatting the output.
 */
class Rock_The_Slackbot_Diff {

	// Define the constants.
	const UNMODIFIED = 0;
	const DELETED    = 1;
	const INSERTED   = 2;

	/**
	 * Returns the diff for two strings.
	 *
	 * The return value is an array, each of whose values
	 * is an array containing two values: a line (or character,
	 * if $compare_characters is true), and one of the constants
	 * DIFF::UNMODIFIED (the line or character is in both strings),
	 * DIFF::DELETED (the line or character is only in the first string),
	 * and DIFF::INSERTED (the line or character is only in the second string).
	 *
	 * The parameters are:
	 *      $string1            - the first string
	 *      $string2            - the second string
	 *      $compare_characters - true to compare characters, and false
	 *          to compare lines; this optional parameter defaults to false
	 */
	public static function compare( $string1, $string2, $compare_characters = false ) {

		// Initialise the sequences and comparison start and end positions.
		$start = 0;
		if ( $compare_characters ) {

			$sequence1 = $string1;
			$sequence2 = $string2;
			$end1 = strlen( $string1 ) - 1;
			$end2 = strlen( $string2 ) - 1;

		} else {

			$sequence1 = preg_split( '/\R/', $string1 );
			$sequence2 = preg_split( '/\R/', $string2 );
			$end1 = count( $sequence1 ) - 1;
			$end2 = count( $sequence2 ) - 1;

		}

		// Skip any common prefix.
		while ( $start <= $end1 && $start <= $end2 && $sequence1[ $start ] == $sequence2[ $start ] ) {
			$start ++;
		}

		// Skip any common suffix.
		while ( $end1 >= $start && $end2 >= $start && $sequence1[ $end1 ] == $sequence2[ $end2 ] ) {
			$end1 --;
			$end2 --;
		}

		// Compute the table of longest common subsequent lengths.
		$table = self::compute_table( $sequence1, $sequence2, $start, $end1, $end2 );

		// Generate the partial diff.
		$partial_diff = self::generate_partial_diff( $table, $sequence1, $sequence2, $start );

		// Generate the full diff.
		$diff = array();
		for ( $index = 0; $index < $start; $index ++ ) {
			$diff[] = array( $sequence1[ $index ], self::UNMODIFIED );
		}

		while ( count( $partial_diff ) > 0 ) {
			$diff[] = array_pop( $partial_diff );
		}

		for ( $index = $end1 + 1; $index < ( $compare_characters ? strlen( $sequence1 ) : count( $sequence1 ) ); $index ++ ) {
			$diff[] = array( $sequence1[ $index ], self::UNMODIFIED );
		}

		// Return the diff.
		return $diff;

	}

	/**
	 * Returns the table of longest common
	 * subsequent lengths for the specified sequences.
	 *
	 * The parameters are:
	 *      $sequence1 - the first sequence
	 *      $sequence2 - the second sequence
	 *      $start     - the starting index
	 *      $end1      - the ending index for the first sequence
	 *      $end2      - the ending index for the second sequence
	 */
	private static function compute_table( $sequence1, $sequence2, $start, $end1, $end2 ) {

		// Determine the lengths to be compared.
		$length1 = $end1 - $start + 1;
		$length2 = $end2 - $start + 1;

		// Initialise the table.
		$table = array( array_fill( 0, $length2 + 1, 0 ) );

		// Loop over the rows.
		for ( $index1 = 1; $index1 <= $length1; $index1 ++ ) {

			// Create the new row.
			$table[ $index1 ] = array( 0 );

			// Loop over the columns.
			for ( $index2 = 1; $index2 <= $length2; $index2 ++ ) {

				// Store the longest common subsequence length.
				if ( $sequence1[ $index1 + $start - 1 ] == $sequence2[ $index2 + $start - 1 ] ) {
					$table[ $index1 ][ $index2 ] = $table[ $index1 - 1 ][ $index2 - 1 ] + 1;
				} else {
					$table[ $index1 ][ $index2 ] = max( $table[ $index1 - 1 ][ $index2 ], $table[ $index1 ][ $index2 - 1 ] );
				}
			}
		}

		// Return the table.
		return $table;

	}

	/**
	 * Returns the partial diff for the specified sequences, in reverse order.
	 *
	 * The parameters are:
	 *      $table      - the table returned by the compute_table function
	 *      $sequence1  - the first sequence
	 *      $sequence2  - the second sequence
	 *      $start      - the starting index
	 */
	private static function generate_partial_diff( $table, $sequence1, $sequence2, $start ) {

		// Initialise the diff.
		$diff = array();

		// Initialise the indices.
		$index1 = count( $table ) - 1;
		$index2 = count( $table[0] ) - 1;

		// Loop until there are no items remaining in either sequence.
		while ( $index1 > 0 || $index2 > 0 ) {

			// Check what has happened to the items at these indices.
			if ( $index1 > 0 && $index2 > 0 && $sequence1[ $index1 + $start - 1 ] == $sequence2[ $index2 + $start - 1 ] ) {

				// Update the diff and the indices.
				$diff[] = array( $sequence1[ $index1 + $start - 1 ], self::UNMODIFIED );
				$index1 --;
				$index2 --;

			} elseif ( $index2 > 0 && $table[ $index1 ][ $index2 ] == $table[ $index1 ][ $index2 - 1 ] ) {

				// Update the diff and the indices.
				$diff[] = array( $sequence2[ $index2 + $start - 1 ], self::INSERTED );
				$index2 --;

			} else {

				// Update the diff and the indices.
				$diff[] = array( $sequence1[ $index1 + $start - 1 ], self::DELETED );
				$index1 --;

			}
		}

		// Return the diff.
		return $diff;

	}

	/**
	 * Returns a diff as a string, where unmodified
	 * lines are prefixed by '  ', deletions are prefixed
	 * by '- ', and insertions are prefixed by '+ '.
	 *
	 * The parameters are:
	 *      $diff       - the diff array
	 *      $separator  - the separator between lines;
	 *          this optional parameter defaults to "\n"
	 */
	public static function to_string( $diff, $separator = "\n" ) {

		// Initialise the string.
		$string = '';

		// Loop over the lines in the diff.
		foreach ( $diff as $line ) {

			// Extend the string with the line.
			switch ( $line[1] ) {
				case self::UNMODIFIED : $string .= '  ' . $line[0];break;
				case self::DELETED    : $string .= '- ' . $line[0];break;
				case self::INSERTED   : $string .= '+ ' . $line[0];break;
			}

			// Extend the string with the separator.
			$string .= $separator;

		}

		// Return the string.
		return $string;

	}

	/**
	 * Returns a diff as an HTML string, where unmodified
	 * lines are contained within 'span' elements, deletions
	 * are contained within 'del' elements, and insertions are
	 * contained within 'ins' elements.
	 *
	 * The parameters are:
	 *      $diff      - the diff array
	 *      $separator - the separator between lines;
	 *          this optional parameter defaults to '<br/>'
	 */
	public static function to_html( $diff, $separator = '<br />' ) {

		// Initialise the HTML.
		$html = '';

		// Loop over the lines in the diff.
		foreach ( $diff as $line ) {

			// Extend the HTML with the line.
			switch ( $line[1] ) {
				case self::UNMODIFIED : $element = 'span'; break;
				case self::DELETED    : $element = 'del';  break;
				case self::INSERTED   : $element = 'ins';  break;
			}

			// Add the HTML element.
			$html .= '<' . $element . '>' . htmlspecialchars( $line[0] ) . '</' . $element . '>';

			// Extend the HTML with the separator.
			$html .= $separator;

		}

		// Return the HTML.
		return $html;

	}

	/**
	 * Returns a diff as an HTML table.
	 *
	 * The parameters are:
	 *      $diff        - the diff array
	 *      $separator   - the separator between lines; this optional parameter
	 *              defaults to '<br />'
	 */
	public static function to_table( $diff, $separator = '<br />' ) {

		// Initialise the HTML.
		$html = '<table class="diff">';

		// Loop over the lines in the diff.
		$index = 0;
		while ( $index < count( $diff ) ) {

			// Determine the line type.
			switch ( $diff[ $index ][1] ) {

				// Display the content on the left and right.
				case self::UNMODIFIED:
					$left_cell = self::get_cell_content( $diff, $separator, $index, self::UNMODIFIED );
					$right_cell = $left_cell;
					break;

				// Display the deleted on the left and inserted content on the right.
				case self::DELETED:
					$left_cell = self::get_cell_content( $diff, $separator, $index, self::DELETED );
					$right_cell = self::get_cell_content( $diff, $separator, $index, self::INSERTED );
					break;

				// Display the inserted content on the right.
				case self::INSERTED:
					$left_cell = '';
					$right_cell = self::get_cell_content( $diff, $separator, $index, self::INSERTED );
					break;

			}

			// Define the class for the cells
			$left_cell_class = ( $left_cell == $right_cell ? 'Unmodified' : ( '' == $left_cell ? 'Blank' : 'Deleted' ) );
			$right_cell_class = ( $left_cell == $right_cell ? 'Unmodified' : ( '' == $right_cell ? 'Blank' : 'Inserted' ) );

			// Extend the HTML with the new row.
			$html .= '<tr>
				<td class="diff' . $left_cell_class . '">' . $left_cell . '</td>
				<td class="diff' . $right_cell_class . '">' . $right_cell . '</td>
			</tr>';

		}

		// Return the HTML.
		return $html . "</table>\n";

	}

	/**
	 * Returns the content of the cell,
	 * for use in the to_table function.
	 *
	 * The parameters are:
	 *      $diff        - the diff array
	 *      $separator   - the separator between lines
	 *      $index       - the current index, passes by reference
	 *      $type        - the type of line
	 */
	private static function get_cell_content( $diff, $separator, &$index, $type ) {

		// Initialise the HTML.
		$html = '';

		// Loop over the matching lines, adding them to the HTML.
		while ( $index < count( $diff ) && $diff[ $index ][1] == $type ) {
			$html .= '<span>' . htmlspecialchars( $diff[ $index ][0] ) . '</span>' . $separator;
			$index ++;
		}

		// Return the HTML.
		return $html;

	}

}
