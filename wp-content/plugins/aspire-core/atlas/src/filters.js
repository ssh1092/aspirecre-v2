// Search conveniences only: overlapping approximate boxes, NOT official CRE submarkets.
export const AREAS = {
 west: {label:'West Houston', bounds:[[-95.85,29.70],[-95.50,29.95]]},
 north: {label:'North Houston', bounds:[[-95.70,29.90],[-95.05,30.25]]},
 southwest: {label:'Southwest Houston', bounds:[[-95.78,29.55],[-95.40,29.77]]},
 katy: {label:'Katy / Richmond', bounds:[[-95.95,29.55],[-95.68,29.90]]},
 humble: {label:'Humble / Atascocita', bounds:[[-95.35,29.90],[-95.05,30.15]]},
};
export const SIZES = {
 small:[0,5000], medium:[5000,10000], large:[10000,25000], larger:[25000,50000], largest:[50000,Infinity],
};
export const defaultFilters = () => ({propertyType:'',transactionType:'for-lease',size:'',area:''});
const positive = value => Number.isFinite(value) && value > 0;
export function usableSf(properties) {
 const m=properties.metrics ?? {};
 if (positive(m.availableSf)) return m.availableSf;
 // Never imply that acreage or an incidental building represents leasable land area.
 if (properties.propertyType?.slug !== 'land' && m.availableSf == null && positive(m.buildingSf)) return m.buildingSf;
 return null;
}
export function filterProperties(features, filters) {
 return features.filter(f => {
  const p=f.properties, transaction=p.transactionType?.slug;
  if (filters.propertyType && p.propertyType?.slug !== filters.propertyType) return false;
  // A dual listing is leasable; the dual option itself matches only that actual term.
  if (filters.transactionType && transaction !== filters.transactionType && !(filters.transactionType==='for-lease' && transaction==='for-sale-or-lease')) return false;
  const range=SIZES[filters.size], sf=usableSf(p);
  if (range && (sf===null || sf<range[0] || sf>=range[1])) return false;
  const box=AREAS[filters.area]?.bounds, [x,y]=f.geometry.coordinates;
  return !box || (x>=box[0][0] && x<=box[1][0] && y>=box[0][1] && y<=box[1][1]);
 });
}
export function propertyMetric(p) {
 const sf=usableSf(p), m=p.metrics ?? {}, format=n=>n.toLocaleString('en-US',{maximumFractionDigits:2});
 if (sf!==null) return `${format(sf)} SF${positive(m.availableSf)?' available':' building'}`;
 if (positive(m.lotAcres)) return `${format(m.lotAcres)} acres`;
 return 'Contact for availability';
}
