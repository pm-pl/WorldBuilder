<?php

declare(strict_types=1);

namespace cosmicpe\worldbuilder\editor\utils\clipboard;

use cosmicpe\worldbuilder\session\utils\Selection;
use Generator;
use pocketmine\math\Vector3;
use pocketmine\world\World;

final class SimpleClipboard implements Clipboard{
	use SimpleClipboardTrait;

	/** @var array<int, ClipboardEntry> */
	private array $entries = [];

	public function asSelection(Vector3 $relative_to) : Selection{
		$selection = new Selection(2);
		$selection->setPoint(0, $relative_to);
		$selection->setPoint(1, $this->maximum->subtractVector($this->minimum)->addVector($relative_to));
		return $selection;
	}

	public function get(int $x, int $y, int $z) : ?ClipboardEntry{
		return $this->entries[World::blockHash($x, $y, $z)] ?? null;
	}

	public function copy(int $x, int $y, int $z, ClipboardEntry $entry) : void{
		$this->entries[World::blockHash($x, $y, $z)] = $entry;
	}

	/**
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 * @return Generator<ClipboardEntry>
	 */
	public function getAll(&$x, &$y, &$z) : Generator{
		foreach($this->entries as $hash => $entry){
			World::getBlockXYZ($hash, $x, $y, $z);
			yield $entry;
		}
	}

	public function getVolume() : int{
		return count($this->entries);
	}
}