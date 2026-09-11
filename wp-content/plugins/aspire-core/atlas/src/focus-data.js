const text=value=>typeof value==='string'?value.trim():'';
const positive=value=>Number.isFinite(value) && value>0;
const number=value=>value.toLocaleString('en-US',{maximumFractionDigits:2});
export function localUrl(value,origin) {
 if(!text(value))return null;
 try {const url=new URL(value,origin);return ['http:','https:'].includes(url.protocol) && url.origin===origin?url.href:null;}catch{return null;}
}
export function focusData(feature,origin) {
 const p=feature?.properties;
 if(!p || !text(p.title))return null;
 const m=p.metrics??{},prices=p.pricing??{},metrics=[];
 const highlights=Array.isArray(p.propertyHighlights)?p.propertyHighlights.map(text).filter(Boolean):[];
 if(positive(m.availableSf))metrics.push({label:'AVAILABLE',value:`${number(m.availableSf)} SF`});
 if(text(prices.leaseRateDisplay))metrics.push({label:'LEASE RATE',value:text(prices.leaseRateDisplay)});
 if(positive(m.buildingSf))metrics.push({label:'BUILDING',value:`${number(m.buildingSf)} SF`});
 if(positive(m.lotAcres))metrics.push({label:'SITE',value:`${number(m.lotAcres)} AC`});
 // Only recognize an exact standalone fact from the stored plain-text highlights.
 // No HTML scraping and no extra REST fields or duplicate metadata definitions.
 const parking=highlights.map(line=>line.match(/^(\d[\d,]*) parking spaces$/i)).find(Boolean);
 if(parking && positive(Number(parking[1].replaceAll(',',''))))metrics.push({label:'PARKING SPACES',value:number(Number(parking[1].replaceAll(',','')))});
 if(text(prices.priceDisplay))metrics.push({label:'PRICE',value:text(prices.priceDisplay)});
 else if(positive(prices.salePrice))metrics.push({label:'PRICE',value:new Intl.NumberFormat('en-US',{style:'currency',currency:'USD',maximumFractionDigits:0}).format(prices.salePrice)});
 const imageUrl=text(p.image?.url)?localUrl(p.image.url,origin):null;
 return {
  title:text(p.displayTitle)||text(p.title),eyebrow:[text(p.propertyType?.label),text(p.transactionType?.label)].filter(Boolean).join(' · '),
  location:[[text(p.location?.city),text(p.location?.state)].filter(Boolean).join(', '),text(p.location?.postalCode)].filter(Boolean).join(' '),
  image:imageUrl?{url:imageUrl,alt:text(p.image?.alt)||text(p.title)}:null,
  permalink:text(p.permalink)?localUrl(p.permalink,origin):null,
  metrics,highlights:highlights.filter(line=>!parking || line!==parking.input).slice(0,5),
 };
}
