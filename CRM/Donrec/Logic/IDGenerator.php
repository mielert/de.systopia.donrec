<?php
/*-------------------------------------------------------+
| SYSTOPIA Donation Receipts Extension                   |
| Copyright (C) 2013-2016 SYSTOPIA                       |
| Author: T. Leichtfuß (leichtfuss -at- systopia.de)     |
| http://www.systopia.de/                                |
+--------------------------------------------------------+
| License: AGPLv3, see LICENSE file                      |
+--------------------------------------------------------*/

/**
 * This class represents generator function for unique IDs
 * based on a pattern string
 */
class CRM_Donrec_Logic_IDGenerator {

  /** the pattern to be used for the ID generation */
  protected $pattern = NULL;
  protected $serial_regexp = "{serial(:[^}]+)?}";
  protected $tokens = array(
    'issue_year' => NULL,
    'contact_id' => NULL
  );
  /**
   * constructor
   *
   * @param $pattern the pattern to be used for the ID
   */
  public function __construct($pattern) {
    # TODO: get the pattern from donrec-configuration
    $pattern = 'foobar_{contact_id}_{serial:123}_{issue_year}';

    # serial-token must occur exactly one time
    $serial_count_regexp = '/' . $this->serial_regexp . '/';
    $count = preg_match_all($serial_count_regexp, $pattern);

    # serial-token must not be between numbers or other tokens (which could be a number aswell)
    $serial_valid_regexp = '/(^|[^0-9}])(' . $this->serial_regexp . ')($|[^0-9{])/';
    $valid = preg_match($serial_valid_regexp, $pattern);

    if ($count != 1 || ! $valid) {
      $msg = "Invalid ID-pattern: '$pattern'";
      error_log($msg);
      throw new Exception($msg);
    }
    $this->pattern = $pattern;
  }


  /**
   * You need to lock the generator so a truely unique ID can be generated
   */
  public function lock() {
    // TODO: implement
  }


  /**
   * Once you're done you have to release
   *  a previously locked generator
   */
  public function release() {
    // TODO: implement
  }

  /**
   * generate a new, unique ID with the pattern passed in the constructor
   *
   * The generator needs to be locked before this can happen.
   *
   * @param $chunk the set of contributions used for this receipt as used in CRM_Donrec_Logic_Engine
   * @return unique ID string
   */
  public function generateID($snapshot_lines) {

    // prepare tokens
    // FIXME: check for occurance
    $contact_id = $snapshot_lines[0]['contact_id'];
    $snapshot_line = (isset($snapshot_lines['id']))? $snapshot_lines : $snapshot_lines[0];
    $this->tokens['contact_id'] = $snapshot_line['contact_id'];
    $this->tokens['issue_year'] = date("Y");

    // get database-infos
    $table = CRM_Donrec_DataStructure::getTableName('zwb_donation_receipt');
    $fields = CRM_Donrec_DataStructure::getCustomFields('zwb_donation_receipt');
    $field = $fields['receipt_id'];

    // prepare pattern and regexp
    $pattern = $this->pattern;
    foreach ($this->tokens as $token => $value) {
      $pattern = str_replace("{" . $token . "}", $value, $pattern);
    }

    // get the length an position of the serial-token
    $serial_regexp = '/' . $this->serial_regexp . '/';
    preg_match($serial_regexp, $pattern, $match, PREG_OFFSET_CAPTURE);
    $serial_token_length = strlen($match[0][0]);
    $serial_token_position = $match[0][1];

    // get everything behind the serial-token
    $serial_token_suffix = substr($pattern, $serial_token_position + $serial_token_length);

    // mysql counts from 1
    $serial_token_position++;

    // build the LOCATE-part of the query
    if ($serial_token_suffix) {
      $length_query = "FOR LOCATE('$serial_token_suffix', `$field`) - $serial_token_position";
    }

    // replace the token to get the mysql-regexp-string
    $mysql_regexp = '^' . preg_replace($serial_regexp, "[0-9]+", $pattern) . '$';

    // TODO: convert to int (basis 10)
    // build and run query
    $query = "
      SELECT MAX(SUBSTRING(`$field` FROM $serial_token_position $length_query))
      FROM `$table`
      WHERE `$field` REGEXP '$mysql_regexp'
    ";
    $last_serial = CRM_Core_DAO::singleValueQuery($query);

    // prepare receipt_id
    if ($last_serial) {
      $receipt_id = preg_replace($serial_regexp, $last_serial + 1, $pattern);
    } else {
      $receipt_id = preg_replace($serial_regexp, 1, $pattern);
    }

    error_log($last_serial);
    error_log($receipt_id);
    return $receipt_id;
  }

}