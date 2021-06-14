<?php //namespace Illuminate\Contracts\Support;

namespace Vasim\IMAP\Extra;

interface Jsonable {

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0);

}
