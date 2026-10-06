const assert=require('node:assert/strict');
const fs=require('node:fs');
const path=require('node:path');
const vm=require('node:vm');
const test=require('node:test');
const source=fs.readFileSync(path.join(__dirname,'..','index.php'),'utf8');
function setup() {
  const elements=new Map();
  const document={getElementById(id){if(!elements.has(id))elements.set(id,{hidden:true,style:{},value:'',textContent:'',classList:{contains:()=>true},setAttribute(){},replaceChildren(){},appendChild(){}});return elements.get(id);},createElement:()=>({style:{}})};
  let now=1000;
  const context=vm.createContext({document,Date:{now:()=>now},clearTimeout(){},checkSerialDuplicate(){},setTimeout(){},isLaptopBatch:()=>false,isMobileScannerLayout:()=>true});
  const start=source.indexOf('let serialScanner=null');
  const end=source.indexOf('\nasync function exportAssetCSV(',start);
  vm.runInContext(source.slice(start,end),context);
  return {context,document,tick:()=>{now+=100;}};
}
test('Target is a narrow actual decode region that fits phone and desktop previews',()=>{
  const {context}=setup();
  for(const [width,height] of [[180,135],[320,240],[620,465]]) {
    const linear=context.serialScanRegion(width,height,'linear');
    assert.ok(linear.width<width && linear.height<height && linear.width>linear.height);
    assert.ok(linear.height>=50);
    const matrix=context.serialScanRegion(width,height,'matrix');
    assert.equal(matrix.width,matrix.height);
    assert.ok(matrix.width<Math.min(width,height));
  }
});
test('Three matching reads require explicit confirmation; changing codes resets agreement',()=>{
  const {context,document,tick}=setup();
  let paused=0;
  const scanner={pause:()=>paused++};
  context.handleSerialRead('NEIGHBOR',scanner);tick();
  context.handleSerialRead('SERIAL123',scanner);tick();
  context.handleSerialRead('SERIAL123',scanner);
  assert.equal(paused,0);tick();
  context.handleSerialRead('SERIAL123',scanner);
  assert.equal(paused,1);
  assert.equal(document.getElementById('serial-scan-value').textContent,'SERIAL123');
  assert.equal(document.getElementById('f-serial').value,'');
  assert.equal(document.getElementById('serial-scan-candidate').hidden,false);
});
test('Control characters and oversized values never become candidates',()=>{
  const {context,document}=setup();
  const scanner={pause(){throw new Error('Should not pause');}};
  for(let repeat=0;repeat<3;repeat++) {context.handleSerialRead('ABC\nXYZ',scanner);context.handleSerialRead('x'.repeat(256),scanner);}
  assert.equal(document.getElementById('serial-scan-candidate').hidden,true);
});
test('Camera failures map to actionable honest feedback',()=>{
  const {context}=setup();
  assert.match(context.serialCameraError({name:'NotAllowedError'}),/permission denied/);
  assert.match(context.serialCameraError({name:'NotFoundError'}),/No camera/);
  assert.match(context.serialCameraError({name:'NotReadableError'}),/other apps/);
  assert.match(context.serialCameraError({name:'OverconstrainedError'}),/unsupported/);
});

test('Accepting a reviewed result writes the serial and stops the camera',async()=>{
  const {context,document}=setup();
  const scanner={pause(){}};
  for(let count=0;count<3;count++)context.handleSerialRead('SERIAL123',scanner);
  context.acceptSerialScan();
  assert.equal(document.getElementById('f-serial').value,'SERIAL123');
  assert.equal(document.getElementById('serial-scanner-wrap').style.display,'none');
});

test('Stopping during camera startup releases the camera after startup resolves',async()=>{
  const {context,document}=setup();
  let resolveStart,stopped=0,cleared=0;
  context.window={isSecureContext:true,Html5Qrcode:true};
  context.navigator={mediaDevices:{getUserMedia(){}}};
  context.loadHtml5QrCode=callback=>callback();
  context.Html5QrcodeSupportedFormats={};
  context.Html5Qrcode=class {
    start(camera,config){config.qrbox(320,240);return new Promise(resolve=>{resolveStart=resolve;});}
    async stop(){stopped++;}
    clear(){cleared++;}
  };
  const starting=context.startSerialScanner();
  await Promise.resolve();await Promise.resolve();
  assert.ok(resolveStart);
  const stopping=context.stopSerialScanner();
  resolveStart();
  await starting;await stopping;
  assert.equal(stopped,1);assert.equal(cleared,1);
  assert.equal(document.getElementById('serial-scanner-wrap').style.display,'none');
});

test('Desktop layout cannot start the serial camera or load the scanner library',async()=>{
  const {context,document}=setup();
  context.isMobileScannerLayout=()=>false;
  context.loadHtml5QrCode=()=>{throw new Error('Desktop must not load scanner');};
  await context.startSerialScanner();
  assert.notEqual(document.getElementById('serial-scanner-wrap').style.display,'block');
});

test('Leaving mobile layout invokes camera cleanup',()=>{
  let onChange,serialStops=0,qrStops=0;
  const media={matches:true,addEventListener:(name,callback)=>{onChange=callback;}};
  const context=vm.createContext({window:{matchMedia:query=>{assert.equal(query,'(max-width: 767px)');return media;}},
    stopSerialScanner:()=>serialStops++,stopScanner:()=>qrStops++,scannerActive:true});
  const start=source.indexOf('const scannerMobileLayout =');
  const end=source.indexOf('\nfunction loadHtml5QrCode(',start);
  vm.runInContext(source.slice(start,end),context);
  assert.equal(context.isMobileScannerLayout(),true);
  media.matches=false;onChange({matches:false});
  assert.equal(context.isMobileScannerLayout(),false);
  assert.equal(serialStops,1);assert.equal(qrStops,1);
});

test('Desktop layout cannot start the asset QR scanner',()=>{
  const context=vm.createContext({isMobileScannerLayout:()=>false,loadHtml5QrCode:()=>{throw new Error('Desktop camera start');}});
  const start=source.indexOf('function startScanner(){');
  const end=source.indexOf('\nfunction stopScanner()',start);
  vm.runInContext(source.slice(start,end),context);
  context.startScanner();
});