export function Skeleton({ className = '' }) {
  return <div aria-hidden="true" className={`animate-pulse rounded bg-gallery-stone/50 ${className}`} />;
}

export default Skeleton;
