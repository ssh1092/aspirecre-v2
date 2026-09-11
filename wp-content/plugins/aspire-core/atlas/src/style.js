// Purpose-built Aspire style against the Protomaps v4 schema. No POI layer or remote styles.
export function atlasStyle(tiles, glyphs) {
 const source = 'houston';
 const layer = (id, type, sourceLayer, paint, rest = {}) => ({ id, type, source, 'source-layer':sourceLayer, paint, ...rest });
 const line = (id, kind, color, width) => layer(id,'line','roads',{'line-color':color,'line-width':['interpolate',['linear'],['zoom'],7,width*.35,11,width,15,width*2.8]}, {filter:['==',['get','kind'],kind],layout:{'line-cap':'round','line-join':'round'}});
 const label = {'text-field':['coalesce',['get','name:en'],['get','name']],'text-font':['Noto Sans Regular'],'text-size':['interpolate',['linear'],['zoom'],7,11,12,15]};
 return {version:8,glyphs,sources:{[source]:{type:'vector',url:'pmtiles://'+tiles}},layers:[
  {id:'background',type:'background',paint:{'background-color':'#09100F'}},
  layer('land','fill','earth',{'fill-color':'#0D1513'}),
  layer('parks','fill','landuse',{'fill-color':'#10201A','fill-opacity':.65},{filter:['in',['get','kind'],['literal',['park','forest','nature_reserve','wood']]]}),
  layer('water','fill','water',{'fill-color':'#071516'},{filter:['==',['geometry-type'],'Polygon']}),
  layer('waterways','line','water',{'line-color':'#123032','line-width':1},{filter:['==',['geometry-type'],'LineString']}),
  layer('buildings','fill','buildings',{'fill-color':'#131D1A','fill-outline-color':'#1B2823'},{minzoom:12,filter:['==',['geometry-type'],'Polygon']}),
  line('minor-roads','minor_road','#1D2926',.7),
  line('major-roads','major_road','#34413D',1.3),
  line('freeways','highway','#56625E',2),
  layer('road-labels','symbol','roads',{'text-color':'#9AA7A1','text-halo-color':'#0D1513','text-halo-width':2},{minzoom:11,filter:['in',['get','kind'],['literal',['highway','major_road']]],layout:{...label,'symbol-placement':'line','text-size':11,'symbol-spacing':300}}),
  layer('route-numbers','symbol','roads',{'text-color':'#C1CDC7','text-halo-color':'#09100F','text-halo-width':3},{minzoom:8,filter:['all',['==',['get','kind'],'highway'],['has','ref']],layout:{'symbol-placement':'line','symbol-spacing':550,'text-field':['get','ref'],'text-font':['Noto Sans Regular'],'text-size':10}}),
  layer('localities','symbol','places',{'text-color':'#B8C1BD','text-halo-color':'#09100F','text-halo-width':2},{filter:['==',['get','kind'],'locality'],layout:{...label,'text-letter-spacing':.08}}),
 ]};
}
