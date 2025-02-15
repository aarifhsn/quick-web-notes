<?php
class Quick_Web_Notes_Service
{
    private $wpdb;
    private $table_name;

    public function __construct($wpdb, $table_name)
    {
        $this->wpdb = $wpdb;
        $this->table_name = $table_name;
    }

    public function get_all_notes()
    {
        return $this->wpdb->get_results(
            "SELECT * FROM {$this->table_name} ORDER BY created_at DESC"
        );
    }

    public function add_note($title, $content)
    {
        return $this->wpdb->insert(
            $this->table_name,
            array(
                'title' => $title,
                'content' => $content
            )
        );
    }

    public function update_note($id, $title, $content)
    {
        return $this->wpdb->update(
            $this->table_name,
            array(
                'title' => $title,
                'content' => $content
            ),
            array('id' => $id)
        );
    }

    public function delete_note($id)
    {
        return $this->wpdb->delete(
            $this->table_name,
            array('id' => $id)
        );
    }

    public function get_note_by_id($id)
    {
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table_name} WHERE id = %d", $id)
        );
    }
}