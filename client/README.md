## Javascript API Documentation

API for GpsPicker is accessible in global object `window.NetteGpsPicker`.

### Loading

Serverside part of GpsPicker is element with custom data attribute `data-nette-gpspicker`. This element can be initialized with method `initialize()`.

```js
NetteGpsPicker.initialize($('[data-nette-gpspicker]').get(0));
```

But there is shortcut implemented as jQuery plugin:

```js
$('[data-nette-gpspicker]').gpspicker();
```

You can chain other jQuery methods after this as usual. If you try to initialize one GpsPicker twice, it will fail silently (second initialization won't proceed).

> You can also override any parameters of GpsPicker by providing options:

> ```js
$('[data-nette-gpspicker]').gpspicker({ ... });
```

Finally you can initialize all standard GpsPickers on the page by calling:

```js
NetteGpsPicker.load();
```

This will be automatically called when document is ready.

### Manipulation

To aquire specific instance of GpsPicker, you can retrieve it from data of the element. Assume that we have GpsPicker with id `foo`.

```js
var gpspicker = $('#foo').data('gpspicker');
```

Returned object has these properties and methods:

<table>
	<tr>
		<th>
			name
		</th>
		<th>
			description
		</th>
	</tr>
	<tr>
		<td>
			<code>.map</code>
		</td>
		<td>
			instance of Google Maps map element
		</td>
	</tr>
	<tr>
		<td>
			<code>.marker</code>
		</td>
		<td>
			marker showing the current position
		</td>
	</tr>
	<tr>
		<td>
			<code>.getValue()</code>
		</td>
		<td>
			getter for current value
		</td>
	</tr>
	<tr>
		<td>
			<code>.setValue(lat, lng)</code>
		</td>
		<td>
			setter of new value
		</td>
	</tr>
</table>

### Change event

You can listen to event, when position is changed:

```js
$('#foo').on('change.gpspicker', function (e, position) {
	console.log('lat: ', position.lat);
	console.log('lng: ', position.lng);
});
```
