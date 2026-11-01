const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
function panel(withPrice) {
 const listeners = {};
 const search = {value:'', addEventListener:(event, fn)=>listeners[event]=fn};
 const price = withPrice ? {textContent:''} : null;
 const row = {dataset:{size:'25 x 5',difference:'1000'}, hidden:false,querySelector:()=>price};
 const empty = {}, count = {}, results = {};
 return {listeners,search,row,price,results,dataset:{baseRate:'22000'},
 querySelector:selector=>({'[data-size-list-search]':search,'[data-size-list-empty]':empty,'[data-size-list-count]':count,'[data-related-results]':results}[selector]),
 querySelectorAll:selector=>selector==='[data-size-list-row]'?[row]:[],hasAttribute:()=>true,addEventListener:()=>{}};
}
const first=panel(true), saved=panel(false), later=panel(true);
vm.runInNewContext(fs.readFileSync('public/js/product-sizes.js','utf8'),{document:{querySelectorAll:selector=>selector==='[data-size-manager]'?[first,saved,later]:[]}});
assert.equal(later.results.hidden,true);
later.search.value='25'; later.listeners.input();
assert.equal(later.results.hidden,false); assert.equal(later.row.hidden,false); assert.equal(later.price.textContent,'23000');
later.search.value='35'; later.listeners.input(); assert.equal(later.results.hidden,true);
later.search.value=''; later.listeners.input(); assert.equal(later.results.hidden,true);
saved.search.value='25'; saved.listeners.input(); assert.equal(saved.row.hidden,false);
console.log('Multi-product search regression passed, including a saved list without a price column.');

saved.dataset.company='Alpha'; saved.row.dataset.company='Beta';
saved.listeners.input(); assert.equal(saved.row.hidden,true);
saved.dataset.company='Beta'; saved.listeners.input(); assert.equal(saved.row.hidden,false);
console.log('Company-specific list filtering passed.');
