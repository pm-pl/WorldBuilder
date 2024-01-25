<?php

declare(strict_types=1);

namespace cosmicpe\worldbuilder\editor\task;

use cosmicpe\worldbuilder\editor\task\utils\ChunkIteratorCursor;
use cosmicpe\worldbuilder\editor\task\utils\EditorTaskUtils;
use cosmicpe\worldbuilder\session\utils\Selection;
use cosmicpe\worldbuilder\utils\Vector3Utils;
use Generator;
use pocketmine\world\format\io\exception\UnsupportedWorldFormatException;
use pocketmine\world\format\io\leveldb\LevelDB;
use pocketmine\world\World;
use ReflectionClassConstant;
use SOFe\AwaitGenerator\Traverser;

class RegenerateChunksEditorTask extends EditorTask{

	public function __construct(World $world, Selection $selection, bool $generate_new_chunks){
		$p0 = $selection->getPoint(0)->asVector3();
		$p0->y = 0;

		$p1 = $selection->getPoint(1)->asVector3();
		$p1->y = 0;

		parent::__construct($world, $selection, (int) ceil(Vector3Utils::calculateVolume($p0, $p1) / 256), $generate_new_chunks);
	}

	public function getName() : string{
		return "regenerate_chunks";
	}

	public function run() : Generator{
		$traverser = new Traverser(EditorTaskUtils::iterateChunks($this->world, $this->selection, $this->generate_new_chunks));
		while(yield from $traverser->next($cursor)){
			$this->world->unloadChunk($cursor->x, $cursor->z, false, false);
			$provider = $this->world->getProvider();
			if(!($provider instanceof LevelDB)){
				throw new UnsupportedWorldFormatException("Regeneration of chunks is only supported for LevelDb worlds");
			}

			static $tag_version = null;
			if($tag_version === null){
				$const = new ReflectionClassConstant($provider, "TAG_VERSION");
				$tag_version = $const->getValue();
			}
			$provider->getDatabase()->delete(LevelDB::chunkIndex($cursor->x, $cursor->z) . $tag_version);
			yield null => Traverser::VALUE;
		}
	}

	public function onCompletion() : void{
		parent::onCompletion();
		$this->world->saveChunks();
	}
}