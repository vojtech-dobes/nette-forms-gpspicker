<?php

namespace VojtechDobes\NetteForms;

use Nette\Latte;


/**
 * Adds manual rendering of GpsPicker
 *
 * @author Vojtěch Dobeš
 */
class GpsPickerMacros extends Latte\Macros\MacroSet
{

	public static function install(Latte\Compiler $compiler)
	{
		$me = new static($compiler);
		$me->addMacro('gpspicker', '$_gpspicker = $_form[%node.word]; $_gpspickerControl = $_gpspicker->getControl(TRUE); echo $_gpspickerControl->addAttributes(%node.array)->startTag()', 'echo $_gpspickerControl->endTag(); unset($_gpspicker, $_gpspickerControl)');
		$me->addMacro('input', array($me, 'macroInput'));
		$me->addMacro('label', array($me, 'macroLabel'), '?></label><?php');
	}



	public function macroInput(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		while ($node->parentNode) {
			if ($node->parentNode->name == 'gpspicker') {
				return $writer->write('echo $_gpspicker->getPartialControl(%node.word)->addAttributes(%node.array)');
			}
			$node = $node->parentNode;
		}
		return FALSE;
	}



	public function macroLabel(Latte\MacroNode $node, Latte\PhpWriter $writer)
	{
		while ($node->parentNode) {
			if ($node->parentNode->name == 'gpspicker') {
				$cmd = 'if ($_label = $_gpspicker->getPartialLabel(%node.word)) echo $_label->addAttributes(%node.array)';
				if ($node->isEmpty = (substr($node->args, -1) === '/')) {
					$node->setArgs(substr($node->args, 0, -1));
					return $writer->write($cmd);
				} else {
					return $writer->write($cmd . '->startTag()');
				}
			}
			$node = $node->parentNode;
		}
		return FALSE;
	}

}
