<?php

class RelationshipField extends CheckboxesField {
	
	public $uniqid;
	public $items_all;
	public $items_selected;
	public $controller;
	public $search;
	public $thumbs;
	public $minEntries;
	public $maxEntries;
	public $variant;
	
	public function __construct() {
		$this->minEntries = is_numeric($this->minEntries) ? $this->minEntries : 0;
		$this->maxEntries = is_numeric($this->maxEntries) ? $this->maxEntries : false;
	}
	
	static public $assets = array(
		'js' => array(
			'relationship-keycode.js',
			'relationship-listbox.js',
			'relationship.js'
		),
		'css' => array(
			'relationship.css'
		)
	);
	
	/**
	 * This field can load a user specified function if set in the blueprint.
	 */
	public function options() {
		if ($this->controller()) {
			return call_user_func($this->controller(), $this);
		} else {
			return parent::options();
		}
	}
	
	/**
	 * Build the html.
	 */
	public function content() {
		$content = new Brick('div');
		$content->addClass('field-content relationship-field');
		$content->attr('data-field', 'relationship');
		
		if ($this->search):
			$content->prepend($this->searchbox());
		endif;
		
		$content->append($this->listboxes());
		
		return $content;
	}
	
	/**
	 * Generates the search box.
	 */
	public function searchbox() {
		$search = new Brick('div');
		$search->addClass('relationship-search');
		$search->append('<i class="icon fa fa-search" aria-hidden="true"></i>');
		
		$input = new Brick('input', null);
		$input->addClass('input');
		$input->attr(array(
			'type'         => 'text',
			'role'         => 'search',
			'autocomplete' => 'off',
		));
		
		if ($this->readonly() || $this->disabled()):
			$input->addClass('input-is-readonly');
			$input->attr('tabindex', '-1');
			$input->attr('disabled', true);
		endif;
		
		$search->append($input);
		
		return $search;
	}
	
	/**
	 * Generates a wrapper div with two listboxes.
	 */
	public function listboxes() {
		// Generate a unique id:
		$this->uniqid = uniqid('relationship_');
		
		// Cache all and selected items:
		$this->items_all = $this->options();
		$this->items_selected = $this->value();
		
		$listboxes = new Brick('div');
		$listboxes->addClass('relationship-lists');
		
		$listboxes->append($this->listbox('available'));
		$listboxes->append($this->listbox('selected'));
		
		return $listboxes;
	}
	
	/**
	 * Generates a listbox with items.
	 */
	public function listbox($type) {
		if ($type === 'available'):
			$list = new Brick('ul');
			$list->attr('aria-multiselectable', 'true');
			
			$items = array_keys($this->items_all);
		else:
			$list = new Brick('ol');
			$list->attr('data-sortable', 'true');
			$list->attr('data-deletable', 'true');
			
			if(strpos($this->variant, 'single-column') === 0){
				$list->attr('hidden', 'true');
			}

			$list->attr('data-min-entries', $this->minEntries);
			$list->attr('data-max-entries', $this->maxEntries);
			
			$items = $this->items_selected;
		endif;
		
		$list->addClass('relationship-list relationship-list--'.$type);
		$list->attr('aria-activedescendant', '');
		$list->attr('aria-label', $this->i18n($this->label));
		$list->attr('role', 'listbox');
		$list->attr('tabindex', '0');
		
		if ($this->readonly() || $this->disabled()):
			$list->attr('aria-readonly', 'true');
			$list->attr('tabindex', '-1');
		endif;
		
		$counter = 0;
		foreach ($items as $key):
			$selected = in_array($key, $this->items_selected);

			if($selected && ($this->variant == 'single-column-hide-previous')){
				continue;
			}

			$counter++;
			$id = $this->uniqid.'_'.$type.'_'.$counter;
			$checked = ($type === 'selected');
			
			$item = $this->item($key, $this->items_all[$key], $id, $selected, $checked);
			$list->append($item);
		endforeach;
		
		return $list;
	}
	
	/**
	 * Generates a single item to be placed in a listbox.
	 */
	public function item($key, $value, $id = '', $selected = false, $checked = false) {
		$item = new Brick('li');
		$item->attr('role', 'option');
		$item->attr('data-key', $key);
		$item->attr('id', $id);
		$item->attr('aria-selected', ($selected) ? 'true' : 'false');
		$item->attr('aria-variant', $this->variant);
		
		if ($this->search):
			$item->attr('data-search-index', trim(mb_strtolower($value)));
		endif;
		
		$item->prepend($this->input($key, $checked));
		
		$item->append('<span class="relationship-item-sort"><i class="icon fa fa-bars" aria-hidden="true"></i></span>');
		$item->append($this->thumbnail($key));
		$item->append(new Brick('span', $value, ['class' => 'relationship-item-label']));
		if(strpos($this->variant, 'single-column') === 0){
			$plusIcon = 'fa-check-square-o ';
			$minusIcon = 'fa-square-o';
		} else {
			$plusIcon = 'fa-plus-circle';
			$minusIcon = 'fa-minus-circle';
		}

		$item->append('<button class="relationship-item-add" tabindex="-1" type="button"><i class="icon fa ' . $plusIcon . '" aria-hidden="true"></i></button>');
		$item->append('<button class="relationship-item-delete" tabindex="-1" type="button"><i class="icon fa ' . $minusIcon . '" aria-hidden="true"></i></button>');

		return $item;
	}
	
	/**
	 * The hidden input field used by Kirby to store selection status.
	 */
	public function input($key = '', $checked = false) {
		$input = new Brick('input', null);
		$input->attr(array(
			'name'         => $this->name() . '[]',
			'type'         => 'checkbox',
			'value'        => $key,
			'checked'      => $checked,
			'required'     => false,
			'readonly'     => $this->readonly(),
			'disabled'     => $this->disabled(),
			'aria-hiddden' => 'true'
		));
		
		return $input;
	}
	
	/**
	 * Generate a thumbnail if requested.
	 */
	public function thumbnail($key) {
		if (!$this->thumbs()):
			return;
		endif;
		
		$thumbs = $this->thumbs();
		$url = '';
		
		if (isset($thumbs['controller'])):
			$url = call_user_func($thumbs['controller'], $key, $this);
		elseif (isset($thumbs['options'])):
			$url = isset($thumbs['options'][$key]) ? $thumbs['options'][$key] : '';
		elseif (isset($thumbs['field'])):
			if ($page = page($key)):
				if ($thumb = $page->content()->get($thumbs['field'])->toFile()):
					$url = $thumb->crop(75)->url();
				endif;
			endif;
		endif;
		
		if ($url):
			$thumbnail = new Brick('img');
			$thumbnail->attr('src', $url);
		else:
			$thumbnail = new Brick('span');
		endif;
		
		$thumbnail->addClass('relationship-item-thumb');
		
		return $thumbnail;
	}
	
	public function validate() {
		if(count($this->value()) < $this->minEntries) return false;
		
		if($this->maxEntries){
			if(count($this->value()) > $this->maxEntries) return false;
		}
		
		return true;
	}
		
}
