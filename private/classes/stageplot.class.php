<?php

/**
 * stageplot.class.php
 * Creates a StagePlot class that extends DatabaseObject, representing a stage plot in the database.
 * This class includes properties corresponding to the stage_plot_tbl columns and methods for CRUD operations.
 * @author: Alyssa Companioni
 * 
 */

class StagePlot extends DatabaseObject
{
  protected static $table_name = 'stage_plot_tbl';
  protected static $db_columns = ['id_stage_plot', 'user_id_usr', 'name_stage_plot', 'description_stage_plot', 'created_at_stage_plot', 'updated_at_stage_plot'];

  static protected $pk = 'id_staplot'; // custom primary key column name
  public $user_id_usr;
  public $name_stage_plot;
  public $description_stage_plot;
  public $created_at_stage_plot;
  public $updated_at_stage_plot;
  public $width = 50; // default width in feet
  public $depth = 30; // default depth in feet

  // Constructor
  public function __construct($args = [])
  {
    $this->user_id_usr = $args['user_id_usr'] ?? null;
    $this->name_stage_plot = $args['name_stage_plot'] ?? '';
    $this->description_stage_plot = $args['description_stage_plot'] ?? '';
    $this->created_at_stage_plot = $args['created_at_stage_plot'] ?? date('Y-m-d H:i:s');
    $this->updated_at_stage_plot = $args['updated_at_stage_plot'] ?? date('Y-m-d H:i:s');
  }
}
