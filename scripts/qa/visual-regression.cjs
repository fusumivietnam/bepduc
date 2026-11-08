#!/usr/bin/env node
'use strict';
/**
 * Candidate screenshots are never implicitly approved as design baselines.
 * Opt in by setting QA_BASELINE_DIR to a reviewer-approved snapshot directory.
 * The directory must contain approval.json and one PNG per captured screenshot.
 */
const fs=require('node:fs');
const path=require('node:path');
const out=process.env.QA_OUTPUT||'artifacts/visual-qa';
const root=path.resolve(out);
const baseline=process.env.QA_BASELINE_DIR;
if(!fs.existsSync(root))throw Error('Missing visual QA screenshot directory: '+root);
const photos=fs.readdirSync(root).filter(f=>f.endsWith('.png')).sort();
if(!photos.length)throw Error('No screenshots to review');
const report={status:'candidate-awaiting-human-approval',screenshots:photos.length,results:[]};
if(baseline){
  const base=path.resolve(baseline);
  const approvalPath=path.join(base,'approval.json');
  if(!fs.existsSync(approvalPath))throw Error('Baseline approval.json is required');
  const approval=JSON.parse(fs.readFileSync(approvalPath,'utf8'));
  if(approval.approved!==true||approval.figmaMaster!=='https://www.figma.com/design/9K2qcVZCJWBeW2gM7OOl5x'||!approval.reviewedBy||!approval.reviewedAt)throw Error('Baseline requires reviewed approval metadata');
  const {PNG}=require('pngjs');
  const pixelmatch=require('pixelmatch');
  const limit=Number(process.env.QA_MAX_DIFF_RATIO??'0.005');
  if(!Number.isFinite(limit)||limit<0||limit>1)throw Error('Invalid QA_MAX_DIFF_RATIO');
  report.status='approved-baseline-comparison';report.maxDiffRatio=limit;
  let failures=0;
  for(const f of photos){
    const target=path.join(base,f);
    if(!fs.existsSync(target))throw Error('Approved baseline missing: '+f);
    const a=PNG.sync.read(fs.readFileSync(target));
    const b=PNG.sync.read(fs.readFileSync(path.join(root,f)));
    if(a.width!==b.width||a.height!==b.height){report.results.push({file:f,status:'dimension-mismatch',actual:[b.width,b.height],expected:[a.width,a.height]});failures++;continue;}
    const diff=new PNG({width:a.width,height:a.height});
    const count=pixelmatch(a.data,b.data,diff.data,a.width,a.height,{threshold:.1});
    const ratio=count/(a.width*a.height);
    report.results.push({file:f,diffPixels:count,ratio,pass:ratio<=limit});
    if(ratio>limit){failures++;fs.writeFileSync(path.join(root,'diff-'+f),PNG.sync.write(diff));}
  }
  fs.writeFileSync(path.join(root,'visual-report.json'),JSON.stringify(report,null,2)+'\n');
  console.log('Visual regression:',report.results.length,'compared;',failures,'failures');
  if(failures)process.exitCode=1;
}else{
  report.results=photos.map(file=>({file,status:'candidate-only'}));
  fs.writeFileSync(path.join(root,'visual-report.json'),JSON.stringify(report,null,2)+'\n');
  console.log('Captured',photos.length,'candidate screenshots; no approved baseline configured, pixel comparison NOT claimed.');
}
