export function LoadingState({ label = 'Loading', className = '' }) {
  return <div role="status" aria-live="polite" className={`p-8 text-center text-gallery-subtle ${className}`}>
    <span className="inline-block h-5 w-5 animate-pulse rounded-full border-2 border-gallery-stone border-t-gallery-ink mr-2" aria-hidden="true" />
    {label}…
  </div>;
}

export default LoadingState;
