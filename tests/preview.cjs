const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');

const html = fs.readFileSync(path.join(__dirname, '..', 'index.php'), 'utf8')
  .replace(/<\?php[\s\S]*?\?>/g, '')
  .replace(/<\?=[\s\S]*?\?>/g, 'Preview User');
const fixtures = process.argv.includes('--fixtures');
const assets = [{id:'SEM-NB01',name:'Dell Latitude 5440 14"',type:'Laptop',serial:'OLD123',assignedTo:"O'Connor",dept:'IT',status:'active',purchaseDate:'2024-01-01',endOfLife:'2030-01-01',cost:750,notes:'Model P137G',createdAt:'2024-01-01 00:00:00'}];
const server = http.createServer((request, response) => {
  if (request.url.startsWith('/api/')) {
    if (fixtures) {
      const url = new URL(request.url, 'http://localhost');
      let data = [];
      if (url.pathname.endsWith('assets.php')) {
        data = url.searchParams.has('stats') ? {total:assets.length,assigned:1,unassigned:assets.length-1,retired:0,totalCost:750,byType:{Laptop:assets.length}}
          : url.searchParams.has('next_id') ? {id:'SEM-NB' + String(assets.length+1).padStart(2,'0')}
          : url.searchParams.has('id') ? assets.find(asset => asset.id === url.searchParams.get('id')) : assets;
        if (request.method === 'POST' && url.searchParams.has('batch')) {
          let body = '';
          request.on('data', chunk => { body += chunk; });
          request.on('end', () => {
            const batch = JSON.parse(body);
            const created = batch.serials.map(serial => {
              const id = 'SEM-NB' + String(assets.length+1).padStart(2,'0');
              assets.push({...assets[0],id,serial,name:batch.name,assignedTo:batch.assigned_to,dept:batch.department,cost:batch.cost});
              return {id,serial};
            });
            response.writeHead(201, {'Content-Type':'application/json'});
            response.end(JSON.stringify({created}));
          });
          return;
        }
      } else if (url.pathname.endsWith('settings.php')) data = {alerts_enabled:'0'};
      response.writeHead(200, {'Content-Type':'application/json'});
      response.end(JSON.stringify(data));
      return;
    }
    response.writeHead(503, { 'Content-Type': 'application/json' });
    response.end(JSON.stringify({ error: 'Preview only: API mocking is required.' }));
    return;
  }
  response.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
  response.end(html);
});
server.listen(0, '127.0.0.1', () => {
  console.log((fixtures ? 'Mock-data preview: ' : 'Markup-only preview: ') + 'http://127.0.0.1:' + server.address().port);
});